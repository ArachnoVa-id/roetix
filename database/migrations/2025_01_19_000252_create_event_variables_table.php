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
            $table->string('event_variables_id', 36)->primary();

            $table->string('event_id', 36)->nullable();
            $table->foreign('event_id')->references('event_id')->on('events')->onDelete('cascade');

            $table->boolean('is_locked')->default(false);
            $table->string('locked_password')->default('')->nullable();

            $table->boolean('is_maintenance')->default(false);
            $table->string('maintenance_title')->default('')->nullable();
            $table->string('maintenance_message')->default('')->nullable();
            $table->date('maintenance_expected_finish')->default(now())->nullable();

            $table->string('logo', 2048)->nullable();
            $table->string('logo_alt')->default('')->nullable();
            $table->string('favicon')->default('')->nullable();

            $table->string('primary_color')->default('');
            $table->string('secondary_color')->default('');
            $table->string('text_primary_color')->default('');
            $table->string('text_secondary_color')->default('');

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
