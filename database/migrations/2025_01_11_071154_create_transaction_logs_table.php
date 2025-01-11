<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('transaction_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('transaction_id');
            $table->uuid('seat_id');
            $table->uuid('user_id');
            $table->string('action');
            $table->string('previous_status')->nullable();
            $table->string('new_status');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('seat_transactions')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('transaction_logs');
    }
};