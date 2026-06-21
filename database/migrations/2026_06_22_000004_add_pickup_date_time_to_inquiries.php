<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Equipment rentals get a pickup date/time (when the equipment is collected) in
// addition to the delivery visit (confirmed_date_time). Stored as a UI-formatted
// string, mirroring confirmed_date_time.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('pickup_date_time', 30)->nullable()->after('confirmed_date_time');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn('pickup_date_time');
        });
    }
};
