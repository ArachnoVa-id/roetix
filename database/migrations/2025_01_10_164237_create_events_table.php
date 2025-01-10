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
            $table->string('event_id', 36)->primary(); // Ganti UUID ke string dengan panjang 36
            $table->string('name');
            $table->enum('category', ['concert', 'sports', 'workshop', 'etc']); // Hapus spasi ekstra
            $table->dateTime('start_date');
            $table->dateTime('end_date'); // Hapus spasi ekstra
            $table->string('location');
            $table->enum('status', ['planned', 'active', 'completed', 'cancelled']); // Hapus spasi ekstra
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
