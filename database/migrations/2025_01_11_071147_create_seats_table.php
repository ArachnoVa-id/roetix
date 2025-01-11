<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('seats', function (Blueprint $table) {
            $table->uuid('seat_id')->primary();
            $table->uuid('venue_id');
            $table->string('seat_number');
            $table->string('position');
            $table->enum('status', ['available', 'booked', 'reserved', 'in_transaction']);
            $table->timestamps();

            $table->foreign('venue_id')
                ->references('venue_id')
                ->on('venues')
                ->onDelete('cascade');

            $table->unique(['venue_id', 'seat_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('seats');
    }
};