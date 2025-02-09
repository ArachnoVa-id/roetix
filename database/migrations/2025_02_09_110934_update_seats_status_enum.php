<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE seats MODIFY COLUMN status ENUM('available', 'booked', 'in_transaction', 'not_available') DEFAULT 'available'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE seats MODIFY COLUMN status ENUM('available', 'booked', 'in_transaction') DEFAULT 'available'");
    }
};