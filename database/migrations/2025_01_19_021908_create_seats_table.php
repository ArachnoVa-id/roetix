<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->string('seat_id', 36)->primary();
            $table->string('venue_id', 36);
            $table->foreign('venue_id')->references('venue_id')->on('venues')->onDelete('cascade');
            $table->string('seat_number');
            $table->string('position');
            // $table->enum('status', ['available', 'booked', 'in_transaction', 'not_available'])
            //     ->default('available');
            // $table->enum('category', ['diamond', 'gold', 'silver'])->default('silver');
            $table->string('row');
            $table->integer('column');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            // $table->enum('status', ['available', 'booked', 'in_transaction', 'not_available'])
            //     ->default('available');
        });
        Schema::dropIfExists('seats');
    }
};
