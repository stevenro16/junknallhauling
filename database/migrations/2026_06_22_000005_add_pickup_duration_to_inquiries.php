<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Equipment pickup gets its own on-site duration (minutes), like the delivery
// visit. Null falls back to 60 minutes.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->integer('pickup_duration_minutes')->nullable()->after('pickup_date_time');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('pickup_duration_minutes');
        });
    }
};
