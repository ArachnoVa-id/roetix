<?php

use App\Enums\EnumVersionType;
use App\Enums\OrderStatus;
use App\Enums\TicketOrderStatus;
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
            $table->string('id', 36)->primary();
            $table->string('snap_token', 36)->nullable();
            $table->string('order_code', 36)->unique();
            $table->string('user_id', 36);
            $table->string('event_id', 36);
            $table->string('team_id', 36);
            $table->dateTime('order_date');
            $table->decimal('total_price', 9, 2);
            $table->enum('status', OrderStatus::getByVersion('v1', EnumVersionType::ARRAY))->default(OrderStatus::getByVersion('v1'));
            $table->dateTime('expired_at');

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
        });

        Schema::create('ticket_order', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('ticket_id', 36);
            $table->string('order_id', 36);
            $table->string('event_id', 36);
            $table->enum('status', TicketOrderStatus::getByVersion('v1', EnumVersionType::ARRAY))->default(TicketOrderStatus::getByVersion('v1'));
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->foreign('ticket_id')->references('id')->on('tickets')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('events')->onDelete('cascade');
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