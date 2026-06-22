<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Inquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CloneCustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_clone_copies_customer_details_only(): void
    {
        $admin = Admin::create(['username' => 'boss', 'role' => 'admin', 'password_hash' => Hash::make('x'), 'must_change_password' => false]);
        $source = Inquiry::create([
            'name' => 'John Smith', 'phone' => '9095550000', 'email' => 'j@e.com',
            'address' => '123 Main St', 'zip_code' => '92399', 'preferred_day' => 'Monday',
            'service_type' => 'equipment', 'equipment_type' => 'Boom Lift', 'equipment_rental_duration' => 3,
            'equipment_rental_unit' => 'days', 'quoted_price' => 450, 'admin_notes' => 'be careful',
            'description' => 'lift for roof', 'status' => 'completed',
        ]);

        $res = $this->withSession(['admin_id' => $admin->id, 'admin_username' => 'boss', 'admin_role' => 'admin', 'admin_must_change' => false])
            ->postJson("/admin/api/inquiries/{$source->id}/clone")
            ->assertCreated();

        $new = Inquiry::find($res->json('inquiry.id'));

        // Customer details carried over.
        $this->assertSame('John Smith', $new->name);
        $this->assertSame('9095550000', $new->phone);
        $this->assertSame('123 Main St', $new->address);
        $this->assertSame('Monday', $new->preferred_day);

        // Service / rental / pricing / notes did NOT carry over.
        $this->assertSame('other', $new->service_type);
        $this->assertNull($new->equipment_type);
        $this->assertNull($new->equipment_rental_duration);
        $this->assertNull($new->quoted_price);
        $this->assertNull($new->admin_notes);
        $this->assertNull($new->description);
        $this->assertSame('new', $new->status);
    }
}
