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
        Schema::create('event_variables', function (Blueprint $table) {
            $table->string('event_variables_id', 36)->primary(); // Menggunakan string dengan panjang 36 untuk UUID
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_maintenance')->default(false);
            $table->string('var_title')->default('');
            $table->date('expected_finish')->default(now());
            $table->string('password')->default('');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_variables');
    }
};
