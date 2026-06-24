<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class QuickQuoteTest extends TestCase
{
    use RefreshDatabase;

    private function adminSession(): static
    {
        $a = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);

        return $this->withSession(['admin_id' => $a->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false]);
    }

    public function test_quick_quote_creates_with_time_and_assignee(): void
    {
        $emp = Admin::create(['username' => 'steven', 'role' => 'employee', 'email' => 's@e.com', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);

        $res = $this->adminSession()->postJson('/admin/calendar/quick-quote', [
            'phone' => '9095551234',
            'datetime' => '2026-06-23T13:30',
            'employee_id' => $emp->id,
        ])->assertCreated();

        $inq = Inquiry::find($res->json('inquiry.id'));
        $this->assertSame('9095551234', $inq->phone);
        $this->assertSame('2026-06-23T13:30', $inq->confirmed_date_time);
        $this->assertSame([$emp->id], $inq->assigned_employee_ids);
        $this->assertSame('quoted', $inq->status);
    }

    public function test_quick_quote_requires_phone(): void
    {
        $this->adminSession()->postJson('/admin/calendar/quick-quote', ['datetime' => '2026-06-23T13:30'])
            ->assertStatus(422);
    }
}
