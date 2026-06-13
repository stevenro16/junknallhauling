<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        // Mirrors ensureDefaultAdmin(): default admin must change password on first login.
        Admin::firstOrCreate(
            ['username' => 'stevenro16'],
            [
                'password_hash'        => Hash::make(env('ADMIN_PASSWORD') ?: 'test'),
                'must_change_password' => true,
            ],
        );
    }
}
