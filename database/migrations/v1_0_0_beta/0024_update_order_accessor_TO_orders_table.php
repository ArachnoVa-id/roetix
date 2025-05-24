<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('snap_token', 'accessor');
        });

        // Modify column length in a separate schema call (for compatibility across DBs)
        Schema::table('orders', function (Blueprint $table) {
            $table->string('accessor', 2048)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('accessor', 'snap_token');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('snap_token', 36)->nullable()->change();
        });
    }
};
