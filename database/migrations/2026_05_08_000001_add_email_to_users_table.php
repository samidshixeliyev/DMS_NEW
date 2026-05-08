<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Nullable — existing users without an email are unaffected.
            // Do NOT add ->unique() here: SQL Server rejects multiple NULLs in a unique
            // index. We create a filtered unique index below that only covers non-NULL rows.
            $table->string('email')->nullable()->after('surname');
        });

        // Filtered unique index: enforces uniqueness only when email IS NOT NULL,
        // which is the correct behaviour for an optional but unique field.
        DB::statement(
            'CREATE UNIQUE INDEX users_email_unique ON users (email) WHERE email IS NOT NULL'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX users_email_unique ON users');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
