<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');                          // customer-facing heading
            $table->json('acknowledgments');                  // array of strings the customer checks
            $table->longText('instructions')->nullable();     // free-form additional terms/instructions
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Seed the existing hardcoded agreement (config/agreement.php) as the default
        // "Dumpster Rental Agreement" so nothing is lost — prohibited items + tire
        // pricing are folded into the instructions field (per the chosen design).
        $cfg = config('agreement');
        $instructions = collect([
            'Prohibited Items: I understand that the following items are NOT allowed to be placed in the dumpster: '.($cfg['prohibited_items'] ?? ''),
            $cfg['tire_pricing'] ?? '',
            $cfg['tire_note'] ?? '',
        ])->filter()->implode("\n\n");

        Schema::getConnection()->table('agreements')->insert([
            'id' => (string) Str::uuid(),
            'title' => 'Dumpster Rental Contract Agreement',
            'acknowledgments' => json_encode(array_values($cfg['acknowledgments'] ?? [])),
            'instructions' => $instructions,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('agreements');
    }
};
