<?php

use App\Models\EquipmentType;
use Illuminate\Database\Migrations\Migration;

// One-off: add the flat-rate "Oversized 10-Yard Dump Trailer" to existing
// installs (the seeder only runs on fresh databases). Idempotent — firstOrCreate
// won't duplicate it if it already exists.
return new class extends Migration
{
    public function up(): void
    {
        EquipmentType::firstOrCreate(
            ['name' => 'Oversized 10-Yard Dump Trailer'],
            [
                'flat_price' => 349,
                'included_days' => 7,
                'included_tons' => 1,
                'price_per_additional_ton' => 84,
                'price_per_additional_day' => 15,
                'active' => true,
                'customer_visible' => true,
            ],
        );
    }

    public function down(): void
    {
        EquipmentType::where('name', 'Oversized 10-Yard Dump Trailer')->delete();
    }
};
