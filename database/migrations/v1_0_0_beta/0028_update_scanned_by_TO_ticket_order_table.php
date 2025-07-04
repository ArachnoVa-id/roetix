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
        Schema::table('ticket_order', function (Blueprint $table) {
            $table->string('scanned_by', 36)->nullable()->after('scanned_at');
            $table->foreign('scanned_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ticket_order', function (Blueprint $table) {
            $table->dropForeign(['scanned_by']);
            $table->dropColumn('scanned_by');
        });
    }
};