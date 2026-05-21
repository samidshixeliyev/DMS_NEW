<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add force_password_change flag — default 0 for existing users, new users get 1 via controller
        DB::statement('ALTER TABLE users ADD force_password_change BIT NOT NULL DEFAULT 0');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE users DROP COLUMN force_password_change');
    }
};
