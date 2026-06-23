<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            // Field-captured photos (data URLs) taken at arrival / departure.
            $table->longText('arrival_photos')->nullable()->after('photos');
            $table->longText('departure_photos')->nullable()->after('arrival_photos');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['arrival_photos', 'departure_photos']);
        });
    }
};
