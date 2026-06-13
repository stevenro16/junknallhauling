<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_status_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inquiry_id');
            $table->string('old_status', 50)->nullable();
            $table->string('new_status', 50);
            $table->string('changed_by', 100)->default('admin');
            $table->string('changed_at', 30);

            $table->foreign('inquiry_id')->references('id')->on('inquiries')->cascadeOnDelete();
            $table->index('inquiry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_status_history');
    }
};
