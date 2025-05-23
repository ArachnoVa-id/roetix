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
        Schema::table('event_variables', function (Blueprint $table) {
            $table->text('tripay_merchant_code_dev')->nullable();
            $table->text('tripay_merchant_code_prod')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_variables', function (Blueprint $table) {

            $table->dropColumn([
                'tripay_merchant_code_dev',
                'tripay_merchant_code_prod'
            ]);
        });
    }
};
