<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Multiple employees per visit / pickup. JSON arrays are the source of truth;
// the legacy single columns are kept (synced to the first assignee) for safety.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->json('assigned_employee_ids')->nullable()->after('assigned_employee_id');
            $table->json('pickup_assigned_employee_ids')->nullable()->after('pickup_assigned_employee_id');
        });

        // Backfill the arrays from the existing single columns.
        foreach (DB::table('inquiries')->select('id', 'assigned_employee_id', 'pickup_assigned_employee_id')->get() as $row) {
            DB::table('inquiries')->where('id', $row->id)->update([
                'assigned_employee_ids' => json_encode($row->assigned_employee_id ? [$row->assigned_employee_id] : []),
                'pickup_assigned_employee_ids' => json_encode($row->pickup_assigned_employee_id ? [$row->pickup_assigned_employee_id] : []),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropColumn(['assigned_employee_ids', 'pickup_assigned_employee_ids']);
        });
    }
};
