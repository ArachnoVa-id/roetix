<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('event_variables', function (Blueprint $table) {
            $table->integer('active_users_threshold')->default(100)->after('ticket_limit');
            $table->integer('active_users_duration')->default(10)->after('active_users_threshold');
        });

        // Populate existing rows with default values for the new columns
        DB::table('event_variables')->update([
            'active_users_threshold' => 100,
            'active_users_duration' => 10,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('event_variables', function (Blueprint $table) {
            $table->dropColumn(['active_users_threshold', 'active_users_duration']);
        });
    }
};
