<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_act_changelogs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('legal_act_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('field_key');          // DB column / logical key  e.g. legal_act_number
            $table->string('field_label');         // Human-readable Azerbaijani label
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamps();

            $table->foreign('legal_act_id')->references('id')->on('legal_acts')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_act_changelogs');
    }
};
