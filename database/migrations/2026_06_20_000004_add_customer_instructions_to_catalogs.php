<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Per-item customer instructions on the service and equipment catalogs. Captured
// now; surfaced in customer-facing workflows later.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_catalog', function (Blueprint $table) {
            $table->text('customer_instructions')->nullable()->after('customer_visible');
        });
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->text('customer_instructions')->nullable()->after('customer_visible');
        });
    }

    public function down(): void
    {
        Schema::table('service_catalog', function (Blueprint $table) {
            $table->dropColumn('customer_instructions');
        });
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->dropColumn('customer_instructions');
        });
    }
};
