<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('timeline_sessions', function (Blueprint $table) {
            $table->string('timeline_id', 36)->primary();
            $table->string('event_id', 36);
            $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
            $table->string('name');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('timeline_sessions');
    }
};