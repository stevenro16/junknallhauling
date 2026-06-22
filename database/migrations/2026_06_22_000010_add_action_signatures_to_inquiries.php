<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Per-action customer signatures: { service_performed: {signature, signed_at}, ... }.
// Each field action (service performed / equipment delivered / picked up) captures
// its own signature. `service_signature` still holds the most recent for legacy display.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->json('signatures')->nullable()->after('service_signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('signatures');
        });
    }
};
