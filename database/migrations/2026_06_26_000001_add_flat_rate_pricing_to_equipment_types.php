<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Flat-rate rental pricing for dumpster/trailer-style equipment: a base price that
// includes a set number of days + tons, with per-ton and per-day overage rates.
// When flat_price is set, the item uses this model instead of hourly/daily.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->decimal('flat_price', 10, 2)->nullable()->after('daily_rate');
            $table->unsignedInteger('included_days')->nullable()->after('flat_price');
            $table->decimal('included_tons', 8, 2)->nullable()->after('included_days');
            $table->decimal('price_per_additional_ton', 10, 2)->nullable()->after('included_tons');
            $table->decimal('price_per_additional_day', 10, 2)->nullable()->after('price_per_additional_ton');
        });
    }

    public function down(): void
    {
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->dropColumn(['flat_price', 'included_days', 'included_tons', 'price_per_additional_ton', 'price_per_additional_day']);
        });
    }
};
