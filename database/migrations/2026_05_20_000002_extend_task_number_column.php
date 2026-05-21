<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Extend task_number column to hold free-form notes (previously was a short task number)
        DB::statement('ALTER TABLE legal_acts ALTER COLUMN task_number NVARCHAR(2000) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE legal_acts ALTER COLUMN task_number NVARCHAR(255) NULL');
    }
};
