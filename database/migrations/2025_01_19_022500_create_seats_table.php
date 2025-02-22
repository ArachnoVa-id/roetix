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
        Schema::create('seats', function (Blueprint $table) {
            $table->string('seat_id', 36)->primary();
            $table->string('venue_id', 36);
            $table->integer('seat_number');
            $table->string('position');
            $table->enum('status', ['available', 'booked', 'reserved', 'in_transaction'])->default('available');
            $table->timestamps();

            $table->foreign('venue_id')->references('venue_id')->on('venues')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
