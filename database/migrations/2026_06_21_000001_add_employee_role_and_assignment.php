<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Employee accounts: a role on the admins table (admin|employee) + an email
// (captured on first login for password resets), and an optional assignment of
// an inquiry/visit to an employee.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->string('role', 20)->default('admin')->after('username');
            $table->string('email')->nullable()->after('role');
        });
        Schema::table('inquiries', function (Blueprint $table) {
            $table->uuid('assigned_employee_id')->nullable()->after('status');
            $table->index('assigned_employee_id');
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            $table->dropColumn(['role', 'email']);
        });
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropIndex(['assigned_employee_id']);
            $table->dropColumn('assigned_employee_id');
        });
    }
};
