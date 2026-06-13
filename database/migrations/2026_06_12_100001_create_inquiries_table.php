<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inquiries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('ref', 20)->unique();              // HAUL-XXXX
            $table->string('name');
            $table->string('phone', 50);
            $table->string('email');
            $table->string('service_type', 50);
            $table->text('description')->nullable();
            $table->longText('photo_base64')->nullable();
            $table->string('photo_mime', 50)->nullable();
            $table->string('status', 30)->default('new');
            $table->text('admin_notes')->nullable();
            $table->double('latitude')->nullable();
            $table->double('longitude')->nullable();
            $table->string('address', 500)->nullable();
            $table->string('zip_code', 20)->nullable();
            $table->string('preferred_day', 50)->nullable();
            $table->string('preferred_time', 50)->nullable();
            $table->string('equipment_type')->nullable();
            $table->integer('equipment_rental_duration')->nullable();
            $table->string('equipment_rental_unit', 20)->nullable();      // hours|days
            $table->string('preferred_contact_method', 20)->default('phone'); // phone|email
            $table->decimal('initial_estimated_quote', 10, 2)->nullable();
            $table->decimal('quoted_price', 10, 2)->nullable();
            $table->boolean('quote_verified')->default(false);
            $table->integer('expected_duration_minutes')->nullable();
            $table->string('confirmed_date_time', 30)->nullable();        // UI-formatted, kept as string
            $table->boolean('address_verified')->default(false);
            $table->boolean('date_time_verified')->default(false);
            $table->boolean('contact_verified')->default(false);
            $table->string('payment_method', 50)->nullable();
            $table->string('payment_date', 30)->nullable();
            $table->text('payment_notes')->nullable();
            $table->timestamps();

            $table->index(['email', 'phone']);
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inquiries');
    }
};
