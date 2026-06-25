<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_catalog', function (Blueprint $table) {
            $table->uuid('agreement_id')->nullable()->after('customer_instructions');
        });

        Schema::table('equipment_types', function (Blueprint $table) {
            $table->uuid('agreement_id')->nullable()->after('customer_instructions');
        });

        // Preserve current behaviour: equipment rentals required an agreement, so
        // attach the default (only) agreement to every existing equipment type.
        $default = DB::table('agreements')->orderBy('created_at')->value('id');
        if ($default) {
            DB::table('equipment_types')->update(['agreement_id' => $default]);
        }
    }

    public function down(): void
    {
        Schema::table('service_catalog', fn (Blueprint $table) => $table->dropColumn('agreement_id'));
        Schema::table('equipment_types', fn (Blueprint $table) => $table->dropColumn('agreement_id'));
    }
};
