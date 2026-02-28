<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_act_executor', function (Blueprint $table) {
            // Drop the unique constraint that prevents multiple executors of same role
            try {
                $table->dropUnique('legal_act_executor_unique');
            } catch (\Exception $e) {
                // Already dropped or doesn't exist
            }
        });
    }

    public function down(): void
    {
        Schema::table('legal_act_executor', function (Blueprint $table) {
            try {
                $table->unique(['legal_act_id', 'executor_id'], 'legal_act_executor_unique');
            } catch (\Exception $e) {}
        });
    }
};