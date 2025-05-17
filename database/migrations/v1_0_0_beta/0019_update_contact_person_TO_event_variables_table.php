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
            $table->string('contact_person')->after('maintenance_expected_finish');
        });

        // Populate existing rows with default values for the new columns
        DB::table('event_variables')->update([
            'contact_person' => 'https://wa.me/6287785917029'
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
