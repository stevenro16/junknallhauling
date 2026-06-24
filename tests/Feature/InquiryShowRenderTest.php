<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InquiryShowRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_edit_page_renders_all_sections(): void
    {
        $admin = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
        $inq = Inquiry::create([
            'name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com',
            'service_type' => 'junk-removal', 'zip_code' => '92399', 'status' => 'scheduled',
            'address' => '5036 Oak St, Mentone CA 92359',
        ]);

        $this->withSession(['admin_id' => $admin->id, 'admin_username' => $admin->username, 'admin_role' => 'admin', 'admin_must_change' => false])
            ->get("/admin/inquiries/{$inq->id}")
            ->assertOk()
            ->assertSee('Job Details')
            ->assertSee('Visit Date &amp; Time', false)
            ->assertSee('Payment')
            ->assertSee('Rental Agreement');   // condensed panel now lives in Job Details
    }

    public function test_detailed_report_renders(): void
    {
        $admin = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
        $inq = Inquiry::create([
            'name' => 'Cust', 'phone' => '9095550000', 'email' => 'c@e.com',
            'service_type' => 'junk-removal', 'status' => 'completed', 'quoted_price' => 250,
            'address' => '5036 Oak St, Mentone CA 92359',
        ]);
        $inq->logStatusChange('scheduled', 'completed', 'boss');

        $this->withSession(['admin_id' => $admin->id, 'admin_username' => $admin->username, 'admin_role' => 'admin', 'admin_must_change' => false])
            ->get("/admin/inquiries/{$inq->id}/report")
            ->assertOk()
            ->assertSee('Detailed Quote Report')
            ->assertSee($inq->ref)
            ->assertSee('Audit Trail');
    }
}
