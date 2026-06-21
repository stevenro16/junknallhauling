<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Threaded notes/comments on a quote. Internal by default; an author (admin or
// employee) can flag a comment as customer-visible so it surfaces on the
// public status-lookup page.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiry_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inquiry_id')->index();
            $table->uuid('author_id')->nullable();      // admins.id (no hard FK — parity with other UUID tables)
            $table->string('author_name')->nullable();  // username snapshot
            $table->text('body');
            $table->boolean('customer_visible')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiry_comments');
    }
};
