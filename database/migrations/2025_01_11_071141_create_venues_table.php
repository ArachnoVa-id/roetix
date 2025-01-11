<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->uuid('venue_id')->primary();
            $table->string('name');
            $table->string('location');
            $table->integer('capacity');
            $table->uuid('contact_info');
            $table->enum('status', ['active', 'inactive', 'under_maintenance']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('venues');
    }
};
