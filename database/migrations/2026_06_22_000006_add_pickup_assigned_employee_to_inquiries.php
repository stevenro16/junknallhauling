<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// An equipment pickup can be assigned to its own person (an employee, or the
// admin themselves) — independent of the delivery visit's assignee.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->uuid('pickup_assigned_employee_id')->nullable()->after('pickup_duration_minutes');
            $table->index('pickup_assigned_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['pickup_assigned_employee_id']);
            $table->dropColumn('pickup_assigned_employee_id');
        });
    }
};
