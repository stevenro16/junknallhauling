<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // Structured address parts (the full `address` column stays as the composed string).
            $table->string('address_street', 255)->nullable()->after('address');
            $table->string('address_city', 120)->nullable()->after('address_street');
            $table->string('address_state', 20)->nullable()->default('CA')->after('address_city');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['address_street', 'address_city', 'address_state']);
        });
    }
};
