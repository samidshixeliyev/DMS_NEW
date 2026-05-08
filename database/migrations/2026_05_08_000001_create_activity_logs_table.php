<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 50);          // login | update | delete
            $table->string('subject_type', 100)->nullable(); // e.g. LegalAct
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['action', 'created_at']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
