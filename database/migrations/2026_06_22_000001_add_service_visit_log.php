<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Field-visit record an assigned employee captures from the job sheet: arrival
// and departure timestamps, plus the customer's signature once service is done.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->timestamp('arrived_at')->nullable()->after('confirmed_date_time');
            $table->timestamp('departed_at')->nullable()->after('arrived_at');
            $table->longText('service_signature')->nullable()->after('departed_at'); // base64 PNG
            $table->timestamp('service_signed_at')->nullable()->after('service_signature');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['arrived_at', 'departed_at', 'service_signature', 'service_signed_at']);
        });
    }
};
