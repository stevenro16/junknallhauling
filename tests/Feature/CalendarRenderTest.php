<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CalendarRenderTest extends TestCase
{
    use RefreshDatabase;

    private function boot(): array
    {
        $admin = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
        $emp = Admin::create(['username' => 'steven', 'role' => 'employee', 'email' => 's@e.com', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
        Inquiry::create([
            'name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com', 'service_type' => 'junk-removal',
            'zip_code' => '92399', 'status' => 'scheduled', 'confirmed_date_time' => '2026-06-23T09:00',
            'assigned_employee_id' => $emp->id,
        ]);

        return [$admin, $emp];
    }

    public function test_main_calendar_renders_with_employee_filter(): void
    {
        [$admin, $emp] = $this->boot();

        $this->withSession(['admin_id' => $admin->id, 'admin_username' => $admin->username, 'admin_role' => 'admin', 'admin_must_change' => false])
            ->get('/admin/calendar')->assertOk()
            ->assertSee('Show:')        // the quick-filter row
            ->assertSee('steven')       // employee button
            ->assertSee('boss (me)');   // admin-as-assignee button
    }

    public function test_calendar_embed_renders_with_filter(): void
    {
        [$admin, $emp] = $this->boot();

        $this->withSession(['admin_id' => $admin->id, 'admin_username' => $admin->username, 'admin_role' => 'admin', 'admin_must_change' => false])
            ->get("/admin/calendar/embed?assignee={$emp->id}&assignee_name=steven")->assertOk()
            ->assertSee('steven');
    }
}
