<?php

use App\Enums\VenueStatus;
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
            $table->string('id', 36)->primary();
            $table->string('team_id', 36)->nullable();
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');

            $table->string('name');
            $table->longText('location');
            $table->string('contact_info', 36);
            $table->enum('status', VenueStatus::getByVersion('v1', 'array'))->default(VenueStatus::getByVersion('v1'));
            $table->timestamps();

            $table->foreign('contact_info')->references('id')->on('user_contacts')->onDelete('cascade');
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
