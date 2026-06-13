<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // NOTE: `key` is a reserved word in MySQL. The schema builder backticks
        // identifiers automatically; never reference `key` in a raw expression.
        Schema::create('service_catalog', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 50)->unique();
            $table->string('label');
            $table->decimal('default_price', 10, 2)->nullable();
            $table->integer('default_duration_minutes')->default(120);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_catalog');
    }
};
