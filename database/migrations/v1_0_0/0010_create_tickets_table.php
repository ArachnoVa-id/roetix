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
            $table->string('id', 36)->primary();
            $table->string('ticket_code', 36)->unique();
            $table->string('event_id', 36);
            $table->string('seat_id', 50);
            $table->string('team_id', 36);
            $table->string('ticket_type', 36);
            $table->string('ticket_category_id', 36)->nullable(); // Added without using 'after'
            $table->decimal('price', 10, 2);
            $table->enum('status', TicketStatus::getByVersion('v1', 'array'))->default(TicketStatus::getByVersion('v1'));

            // foreign keys
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('seat_id')->references('id')->on('seats')->onDelete('cascade');
            $table->foreign('ticket_category_id')->references('id')->on('ticket_categories')->onDelete('set null');

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
