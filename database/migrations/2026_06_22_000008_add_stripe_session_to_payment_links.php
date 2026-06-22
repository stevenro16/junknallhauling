<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Correlate a payment link with its Stripe Checkout Session (for confirm-on-return
// and webhook lookups / idempotency).
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->string('stripe_session_id', 255)->nullable()->after('payment_method');
            $table->index('stripe_session_id');
        });
    }

    public function down(): void
    {
        Schema::table('payment_links', function (Blueprint $table) {
            $table->dropIndex(['stripe_session_id']);
            $table->dropColumn('stripe_session_id');
        });
    }
};
