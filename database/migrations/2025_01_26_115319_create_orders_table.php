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
        Schema::create('orders', function (Blueprint $table) {
            $table->string('order_id', 36)->primary();
            $table->string('user_id', 36);
            $table->string('team_id', 36);
            $table->string('coupon_id', 36);
            $table->datetime('order_date');
            $table->decimal('total_price', 9, 2);
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('coupon_id')->references('coupon_id')->on('coupons')->onDelete('cascade');
        });

        Schema::create('ticket_order', function (Blueprint $table) {
            $table->id();
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
