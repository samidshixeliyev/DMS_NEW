<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('legal_act_executor', function (Blueprint $table) {
            $table->text('task_description')->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('legal_act_executor', function (Blueprint $table) {
            $table->dropColumn('task_description');
        });
    }
};
