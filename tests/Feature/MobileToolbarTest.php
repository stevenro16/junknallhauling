<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MobileToolbarTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): static
    {
        $a = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);

        return $this->withSession(['admin_id' => $a->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false]);
    }

    public function test_default_toolbar_is_calendar_and_quotes(): void
    {
        $this->assertSame(['calendar', 'inquiries'], SiteContent::list('admin_mobile_tools'));
    }

    public function test_admin_can_customize_toolbar_tools(): void
    {
        $this->adminSession()->patchJson('/admin/api/content', [
            'content' => ['admin_mobile_tools' => ['calendar', 'eod', 'customers']],
        ])->assertOk();

        SiteContent::forgetCache();
        $this->assertSame(['calendar', 'eod', 'customers'], SiteContent::list('admin_mobile_tools'));
    }
}
