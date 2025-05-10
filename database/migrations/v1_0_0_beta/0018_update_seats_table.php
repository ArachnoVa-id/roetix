<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            // Add unique constraint
            $table->unique(['seat_number', 'venue_id'], 'seats_seat_number_venue_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('seats', function (Blueprint $table) {
            // Drop the unique constraint
            $table->dropUnique('seats_seat_number_venue_id_unique');
        });
    }
};
