<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AdminAccountController extends Controller
{
    public function index(): JsonResponse
    {
        $admins = Admin::orderBy('created_at')->get()
            ->map(fn (Admin $a) => [
                'id' => $a->id,
                'username' => $a->username,
                'role' => $a->role,
                'email' => $a->email,
                'must_change_password' => $a->must_change_password,
                'created_at' => $a->created_at,
                'updated_at' => $a->updated_at,
            ]);

        return response()->json(['admins' => $admins]);
    }

    public function store(Request $request): JsonResponse
    {
        $username = trim((string) $request->input('username'));
        $password = (string) $request->input('password');

        if ($username === '' || strlen($password) < 6) {
            return response()->json(['error' => 'Username and password (min 6 chars) required'], 400);
        }

        if (Admin::where('username', $username)->exists()) {
            return response()->json(['error' => 'Username already exists'], 409);
        }

        $role = $request->input('role') === 'employee' ? 'employee' : 'admin';

        // New accounts must change their password on first login.
        $admin = Admin::create([
            'username' => $username,
            'role' => $role,
            'password_hash' => Hash::make($password),
            'must_change_password' => true,
        ]);

        return response()->json([
            'admin' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'role' => $admin->role,
                'email' => $admin->email,
                'must_change_password' => $admin->must_change_password,
                'created_at' => $admin->created_at,
            ],
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $admin = Admin::find($id);

        if (! $admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        if ($request->input('action') === 'reset_password') {
            $newPassword = (string) $request->input('newPassword');

            if (strlen($newPassword) < 6) {
                return response()->json(['error' => 'New password must be at least 6 characters'], 400);
            }

            $admin->update([
                'password_hash' => Hash::make($newPassword),
                'must_change_password' => true,
            ]);

            return response()->json(['success' => true]);
        }

        return response()->json(['error' => 'Invalid action'], 400);
    }

    public function destroy(string $id): JsonResponse
    {
        if (Admin::count() <= 1) {
            return response()->json(['error' => 'Cannot delete the last admin account'], 400);
        }

        $admin = Admin::find($id);

        if (! $admin) {
            return response()->json(['error' => 'Admin not found'], 404);
        }

        $admin->delete();

        return response()->json(['success' => true]);
    }
}
