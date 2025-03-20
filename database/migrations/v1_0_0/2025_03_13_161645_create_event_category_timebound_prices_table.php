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
            $table->string('timeline_id', 36);
            $table->decimal('price', 10, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('ticket_category_id', 'fk_ectbp_ticket_category')
                ->references('ticket_category_id')
                ->on('ticket_categories')
                ->onDelete('cascade');

            $table->foreign('timeline_id', 'fk_ectbp_timeline')
                ->references('timeline_id')
                ->on('timeline_sessions')
                ->onDelete('cascade');

            // Unique constraint dengan nama yang lebih pendek
            $table->unique(['ticket_category_id', 'timeline_id'], 'unique_ticket_timeline');
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
