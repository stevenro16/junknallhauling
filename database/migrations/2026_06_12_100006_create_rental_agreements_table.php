<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rental_agreements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 128)->unique();           // 64-hex one-time link
            $table->uuid('inquiry_id');
            $table->longText('form_data')->default('{}');
            $table->longText('signature_base64')->nullable();
            $table->string('signed_at', 30)->nullable();
            $table->string('cancelled_at', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('expires_at', 30)->nullable();
            $table->timestamps();

            $table->foreign('inquiry_id')->references('id')->on('inquiries')->cascadeOnDelete();
            $table->index('token');
            $table->index('inquiry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_agreements');
    }
};
