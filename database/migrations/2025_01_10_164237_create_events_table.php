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
        Schema::create('events', function (Blueprint $table) {
            $table->string('event_id', 36)->primary();
            $table->string('team_id', 36);
            $table->foreign('team_id')->references('team_id')->on('teams')->onDelete('cascade');

            // Kolom venue_id dengan nilai default '123'
            $table->string('venue_id', 36);
            $table->foreign('venue_id')->references('venue_id')->on('venues')->onDelete('cascade');

            // $table->foreign('event_variables_id')->references('event_variables_id')->on('event_variables')->onDelete('cascade');
            $table->string('name');
            $table->string('slug');
            $table->enum('category', ['concert', 'sports', 'workshop', 'etc']);
            $table->dateTime('start_date');
            $table->dateTime('event_date');
            $table->string('location');
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
