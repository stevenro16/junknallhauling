<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Tokenized payment links the admin sends to customers. The public page shows
// the quoted amount and records payment. No live gateway yet — `paid_at` is set
// when the customer completes the (placeholder) payment; swap in Stripe/etc. later.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_links', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('token', 128)->unique();
            $table->uuid('inquiry_id');
            $table->decimal('amount', 10, 2);                 // quoted price snapshot
            $table->string('paid_at', 30)->nullable();
            $table->string('cancelled_at', 30)->nullable();
            $table->string('payment_method', 50)->nullable(); // recorded at pay time
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
        Schema::dropIfExists('payment_links');
    }
};
