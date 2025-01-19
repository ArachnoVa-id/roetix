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
            $table->string('section_id', 36);
            $table->string('seat_number');
            $table->string('position');
            $table->enum('status', ['available', 'booked', 'reserved', 'in_transaction'])->default('available');
            $table->enum('category', ['diamond', 'gold', 'silver'])->default('silver');
            $table->string('row');
            $table->integer('column');
            $table->timestamps();

            $table->foreign('section_id')
                  ->references('id')
                  ->on('sections')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seats');
    }
};
