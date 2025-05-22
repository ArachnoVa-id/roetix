<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Enums\UserRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $enumValues = UserRole::getByVersion('v2', 'array');
        $enumList = "'" . implode("','", $enumValues) . "'";

        DB::statement("ALTER TABLE users MODIFY role ENUM($enumList) DEFAULT '" . UserRole::getByVersion('v2') . "'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $enumValues = UserRole::getByVersion('v1', 'array');
        $enumList = "'" . implode("','", $enumValues) . "'";

        DB::statement("ALTER TABLE users MODIFY role ENUM($enumList) DEFAULT '" . UserRole::getByVersion('v1') . "'");
    }
};
