<?php

use App\Enums\EnumVersionType;
use App\Enums\EventStatus;
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
        Schema::create('events', function (Blueprint $table) {
            $table->string('id', 36)->primary();
            $table->string('team_id', 36);
            $table->foreign('team_id')->references('id')->on('teams')->onDelete('cascade');

            $table->string('venue_id', 36);
            $table->foreign('venue_id')->references('id')->on('venues')->onDelete('cascade');

            $table->string('name');
            $table->string('slug');
            $table->dateTime('start_date');
            $table->dateTime('event_date');
            $table->longText('location');
            $table->enum('status', EventStatus::getByVersion('v1', EnumVersionType::ARRAY))->default(EventStatus::getByVersion('v1'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
