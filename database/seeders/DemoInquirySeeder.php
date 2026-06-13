<?php

namespace Database\Seeders;

use App\Models\Inquiry;
use Illuminate\Database\Seeder;

class DemoInquirySeeder extends Seeder
{
    public function run(): void
    {
        // Only seed demo leads when the table is empty (mirrors seedDemoInquiries()).
        if (Inquiry::count() > 0) {
            return;
        }

        Inquiry::create([
            'name' => 'Maria Gonzalez', 'phone' => '(909) 555-1234', 'email' => 'maria.g@example.com',
            'service_type' => 'junk-removal',
            'description' => 'Old couch, broken dresser, and about 8 bags of yard waste from a garage cleanout.',
            'zip_code' => '92399',
        ]);

        Inquiry::create([
            'name' => 'James Patel', 'phone' => '909-555-9876', 'email' => 'james.patel@example.com',
            'service_type' => '10yd-dumpster',
            'description' => 'Renovation debris - drywall, tile, and some lumber. Need it for 3 days.',
            'zip_code' => '92374',
        ]);

        $last = Inquiry::create([
            'name' => 'Sarah Kline', 'phone' => '(951) 555-0199', 'email' => 'sarah.k@example.com',
            'service_type' => 'equipment',
            'description' => 'Need a scissor lift for one day to reach a roof gutter.',
            'zip_code' => '92320',
            'equipment_type' => 'Scissor Lift (19-26 ft)',
        ]);

        // Attach a tiny 1x1 PNG + admin note to the most recent demo inquiry.
        $last->update([
            'photo_base64' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=',
            'photo_mime'   => 'image/png',
            'admin_notes'  => 'Customer requested same-day if possible.',
        ]);
    }
}
