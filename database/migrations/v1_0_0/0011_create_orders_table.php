<?php

use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('order_id', 36)->primary();
            $table->string('order_code', 36)->unique();
            $table->string('user_id', 36);
            $table->string('event_id', 36);
            $table->string('team_id', 36);
            $table->dateTime('order_date');
            $table->decimal('total_price', 9, 2);
            $table->enum('status', OrderStatus::values())->default(OrderStatus::PENDING);
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
            $table->foreign('team_id')->references('team_id')->on('teams')->onDelete('cascade');
        });

        Schema::create('ticket_order', function (Blueprint $table) {
            $table->string('ticket_id', 36);
            $table->string('order_id', 36);
            $table->string('event_id', 36);

            $table->foreign('ticket_id')->references('ticket_id')->on('tickets')->onDelete('cascade');
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
        Schema::dropIfExists('ticket_order');
    }
};
