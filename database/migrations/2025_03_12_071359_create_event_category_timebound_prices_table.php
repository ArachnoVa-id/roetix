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
        Schema::create('event_category_timebound_prices', function (Blueprint $table) {
            $table->string('timebound_price_id', 36)->primary();
            $table->string('ticket_category_id', 36);
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('ticket_category_id')
                  ->references('ticket_category_id')
                  ->on('ticket_categories')
                  ->onDelete('cascade');

            $table->index('start_date');
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_category_timebound_prices');
    }
};