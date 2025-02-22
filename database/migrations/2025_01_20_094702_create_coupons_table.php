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
        Schema::create('coupons', function (Blueprint $table) {
            $table->string('coupon_id', 36)->primary();
            $table->string('event_id', 36);
            $table->string('name');
            $table->string('code');
            $table->decimal('discount_amount', 10, 2)->default(0.00);
            $table->dateTime('expiry_date');
            $table->integer('quantity');
            $table->string('applicable_categories', 36);
            $table->enum('status', ['active', 'expired', 'used'])->default('active');
            $table->timestamps();

            $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');
            $table->foreign('applicable_categories')->references('ticket_category_id')->on('ticket_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
