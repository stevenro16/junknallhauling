<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Separate "customer visible" (shown on the public quote form) from "active"
// (usable by admins internally). Defaults to true so every existing catalog
// item keeps showing to customers exactly as it does today.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_catalog', function (Blueprint $table) {
            $table->boolean('customer_visible')->default(true)->after('active');
        });
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->boolean('customer_visible')->default(true)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('service_catalog', function (Blueprint $table) {
            $table->dropColumn('customer_visible');
        });
        Schema::table('equipment_types', function (Blueprint $table) {
            $table->dropColumn('customer_visible');
        });
    }
};
