<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Performance indexes for hot query paths:
 *  - active() scopes (is_deleted)
 *  - DataTable filtering and ordering on legal_acts
 *  - visibility scoping on executors.department_id
 *  - status-log lookups for the "latest log per executor" pattern
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('legal_acts', function (Blueprint $table) {
            $table->index('is_deleted', 'legal_acts_is_deleted_idx');
            $table->index('organization_id', 'legal_acts_org_idx');
            $table->index('execution_deadline', 'legal_acts_deadline_idx');
            $table->index('legal_act_date', 'legal_acts_date_idx');
        });

        Schema::table('executors', function (Blueprint $table) {
            $table->index('department_id', 'executors_dept_idx');
            $table->index('is_deleted', 'executors_is_deleted_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->index('is_deleted', 'users_is_deleted_idx');
            $table->index('department_id', 'users_dept_idx');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->index('parent_id', 'departments_parent_idx');
            $table->index(['is_deleted', 'can_assign'], 'departments_active_assign_idx');
        });

        Schema::table('executor_status_logs', function (Blueprint $table) {
            $table->index('approval_status', 'esl_approval_status_idx');
            // Speeds up the "latest log per legal_act" lookups
            $table->index(['legal_act_id', 'created_at'], 'esl_act_created_idx');
        });

        Schema::table('execution_notes', function (Blueprint $table) {
            $table->index('is_deleted', 'execution_notes_is_deleted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('legal_acts', function (Blueprint $table) {
            $table->dropIndex('legal_acts_is_deleted_idx');
            $table->dropIndex('legal_acts_org_idx');
            $table->dropIndex('legal_acts_deadline_idx');
            $table->dropIndex('legal_acts_date_idx');
        });

        Schema::table('executors', function (Blueprint $table) {
            $table->dropIndex('executors_dept_idx');
            $table->dropIndex('executors_is_deleted_idx');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_is_deleted_idx');
            $table->dropIndex('users_dept_idx');
        });

        Schema::table('departments', function (Blueprint $table) {
            $table->dropIndex('departments_parent_idx');
            $table->dropIndex('departments_active_assign_idx');
        });

        Schema::table('executor_status_logs', function (Blueprint $table) {
            $table->dropIndex('esl_approval_status_idx');
            $table->dropIndex('esl_act_created_idx');
        });

        Schema::table('execution_notes', function (Blueprint $table) {
            $table->dropIndex('execution_notes_is_deleted_idx');
        });
    }
};
