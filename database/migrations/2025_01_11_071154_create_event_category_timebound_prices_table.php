<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('event_category_timebound_prices', function (Blueprint $table) {
            $table->uuid('timebound_price_id')->primary();
            $table->uuid('ticket_category_id');
            $table->datetime('start_date');
            $table->datetime('end_date');
            $table->decimal('price', 10, 2);
            $table->timestamps();

            $table->foreign('ticket_category_id')
                ->references('ticket_category_id')
                ->on('ticket_categories')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('event_category_timebound_prices');
    }
};