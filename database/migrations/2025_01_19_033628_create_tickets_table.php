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
        Schema::create('tickets', function (Blueprint $table) {
            $table->string('ticket_id', 36)->primary();
            $table->string('event_id', 36);
            $table->string('seat_id', 36);
            $table->string('team_id', 36);
            // $table->string('order_id', 36);
            $table->enum('ticket_type', ['standard', 'VIP'])->default('standard');
            $table->decimal('price', 5, 2)->default(123.45);
            $table->enum('status', ['available', 'sold', 'reserved'])->default('available');
            $table->timestamps();
            
            // foreign key
            $table->foreign('team_id')->references('team_id')->on('teams')->onDelete('cascade');
            $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
            $table->foreign('seat_id')->references('seat_id')->on('seats')->onDelete('cascade');
            // $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
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
