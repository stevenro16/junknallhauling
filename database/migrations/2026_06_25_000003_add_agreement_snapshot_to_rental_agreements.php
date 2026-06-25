<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_agreements', function (Blueprint $table) {
            // Which agreement template this signing record is for.
            $table->uuid('agreement_id')->nullable()->after('inquiry_id');
            // Frozen copy of the agreement content (title/acknowledgments/instructions)
            // captured at signing time, so the signed terms can always be referenced
            // even if the template is later edited.
            $table->longText('content_snapshot')->nullable()->after('form_data');
        });

        // Backfill existing rows to the default agreement so the admin view/report
        // keeps rendering terms for already-created links.
        $default = DB::table('agreements')->orderBy('created_at')->value('id');
        if ($default) {
            DB::table('rental_agreements')->whereNull('agreement_id')->update(['agreement_id' => $default]);
        }
    }

    public function down(): void
    {
        Schema::table('rental_agreements', function (Blueprint $table) {
            $table->dropColumn(['agreement_id', 'content_snapshot']);
        });
    }
};
