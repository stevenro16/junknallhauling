<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showLogin(Request $request)
    {
        if ($request->session()->has('admin_id')) {
            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $admin = Admin::where('username', $data['username'])->first();

        if (! $admin || ! Hash::check($data['password'], $admin->password_hash)) {
            return response()->json(['error' => 'Invalid username or password'], 401);
        }

        $this->setSession($request, $admin);
        $request->session()->regenerate();

        return response()->json([
            'success'            => true,
            'mustChangePassword' => $admin->must_change_password,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->session()->forget(['admin_id', 'admin_username', 'admin_must_change']);
        $request->session()->regenerate();

        return response()->json(['success' => true]);
    }

    public function me(Request $request): JsonResponse
    {
        $admin = Admin::find($request->session()->get('admin_id'));

        if (! $admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        return response()->json([
            'id'                   => $admin->id,
            'username'             => $admin->username,
            'must_change_password' => $admin->must_change_password,
            'created_at'           => $admin->created_at,
        ]);
    }

    public function updateMe(Request $request): JsonResponse
    {
        $admin = Admin::find($request->session()->get('admin_id'));

        if (! $admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        $newUsername = trim((string) $request->input('newUsername'));

        if ($newUsername === '') {
            return response()->json(['error' => 'Username is required'], 400);
        }

        if (Admin::where('username', $newUsername)->where('id', '!=', $admin->id)->exists()) {
            return response()->json(['error' => 'Username already taken'], 409);
        }

        $admin->update(['username' => $newUsername]);
        $request->session()->put('admin_username', $newUsername);

        return response()->json(['success' => true, 'username' => $newUsername]);
    }

    public function showChangePassword()
    {
        return view('admin.change-password');
    }

    public function changePassword(Request $request): JsonResponse
    {
        $admin = Admin::find($request->session()->get('admin_id'));

        if (! $admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        $newPassword     = (string) $request->input('newPassword');
        $currentPassword = $request->input('currentPassword');

        if (strlen($newPassword) < 6) {
            return response()->json(['error' => 'Password must be at least 6 characters'], 400);
        }

        // Self-service change (from dashboard) supplies the current password.
        if ($currentPassword && ! Hash::check($currentPassword, $admin->password_hash)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        $admin->update([
            'password_hash'        => Hash::make($newPassword),
            'must_change_password' => false,
        ]);

        $request->session()->put('admin_must_change', false);

        return response()->json(['success' => true]);
    }

    private function setSession(Request $request, Admin $admin): void
    {
        $request->session()->put('admin_id', $admin->id);
        $request->session()->put('admin_username', $admin->username);
        $request->session()->put('admin_must_change', $admin->must_change_password);
    }
}
