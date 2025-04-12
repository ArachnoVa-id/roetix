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
        Schema::create('teams', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('name');
            $table->integer('vendor_quota')->default(0);
            $table->integer('event_quota')->default(0);
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('user_team', function (Blueprint $table) {
            $table->string('team_id', 36);
            $table->string('user_id', 36);

            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
        Schema::dropIfExists('user_team');
    }
};
