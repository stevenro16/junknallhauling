<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Per-admin notification settings: which events they want to be notified about and
// on which channels (email / SMS), plus where to send them. Stored as JSON; sending
// is not wired up yet — this is just the saved control panel.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->json('notification_preferences')->nullable()->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn('notification_preferences');
        });
    }
};
