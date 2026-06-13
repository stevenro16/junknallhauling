<?php

namespace Tests\Feature;

use App\Models\Admin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminAuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(bool $mustChange = true): Admin
    {
        return Admin::create([
            'username'             => 'stevenro16',
            'password_hash'        => Hash::make('test'),
            'must_change_password' => $mustChange,
        ]);
    }

    public function test_guest_is_redirected_from_admin(): void
    {
        $this->get('/admin')->assertRedirect(route('admin.login'));
    }

    public function test_login_rejects_bad_credentials(): void
    {
        $this->makeAdmin();
        $this->postJson(route('admin.login.post'), ['username' => 'stevenro16', 'password' => 'wrong'])
            ->assertStatus(401);
    }

    public function test_login_succeeds_and_flags_must_change(): void
    {
        $this->makeAdmin();

        $this->postJson(route('admin.login.post'), ['username' => 'stevenro16', 'password' => 'test'])
            ->assertOk()
            ->assertJson(['success' => true, 'mustChangePassword' => true]);

        $this->assertNotNull(session('admin_id'));
    }

    public function test_must_change_password_blocks_dashboard(): void
    {
        $admin = $this->makeAdmin();
        $this->withSession([
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_must_change' => true,
        ])->get('/admin')->assertRedirect(route('admin.change-password'));
    }

    public function test_change_password_clears_flag_and_grants_access(): void
    {
        $admin = $this->makeAdmin();

        $this->withSession([
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_must_change' => true,
        ])->postJson(route('admin.change-password.update'), ['newPassword' => 'newsecret'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertFalse($admin->fresh()->must_change_password);
        $this->assertTrue(Hash::check('newsecret', $admin->fresh()->password_hash));

        // After clearing the flag, the dashboard is reachable.
        $this->withSession([
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_must_change' => false,
        ])->get('/admin')->assertOk();
    }

    public function test_cannot_delete_last_admin(): void
    {
        $admin = $this->makeAdmin(false);
        $this->withSession([
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_must_change' => false,
        ])->deleteJson(route('admin.admins.destroy', $admin->id))
            ->assertStatus(400);
    }

    public function test_logout_clears_session(): void
    {
        $admin = $this->makeAdmin(false);
        $this->withSession([
            'admin_id' => $admin->id,
            'admin_username' => $admin->username,
            'admin_must_change' => false,
        ])->postJson(route('admin.logout'))->assertOk();
    }
}
