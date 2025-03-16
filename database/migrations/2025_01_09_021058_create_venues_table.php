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
        Schema::create('venues', function (Blueprint $table) {
            $table->string('venue_id', 36)->primary();
            $table->string('team_id', 36)->nullable();
            $table->foreign('team_id')->references('team_id')->on('teams')->onDelete('cascade');

            $table->string('name');
            $table->string('location');
            $table->integer('capacity');
            $table->string('contact_info', 36);
            $table->enum('status', ['active', 'inactive', 'under_maintenance'])->default('active');
            $table->timestamps();

            $table->foreign('contact_info')->references('contact_id')->on('user_contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
