<?php

use App\Enums\TicketStatus;
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
        Schema::create('tickets', function (Blueprint $table) {
            $table->string('ticket_id', 36)->primary();
            $table->string('event_id', 36);
            $table->string('seat_id', 50);
            $table->string('team_id', 36);
            $table->string('ticket_type', 36);
            $table->decimal('price', 10, 2);
            $table->enum('status', TicketStatus::toArray())->default(TicketStatus::AVAILABLE);
            $table->timestamps();
            
            // foreign key
            $table->foreign('team_id')->references('team_id')->on('teams')->onDelete('cascade');
            $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
            $table->foreign('seat_id')->references('seat_id')->on('seats')->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
