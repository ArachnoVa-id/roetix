<?php

use App\Enums\EnumVersionType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Enums\UserRole;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $enumValues = UserRole::getByVersion('v2', EnumVersionType::ARRAY);
        $enumList = "'" . implode("','", $enumValues) . "'";

        DB::statement("ALTER TABLE users MODIFY role ENUM($enumList) DEFAULT '" . UserRole::getByVersion('v2') . "'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $enumValues = UserRole::getByVersion('v1', EnumVersionType::ARRAY);
        $enumList = "'" . implode("','", $enumValues) . "'";

        DB::statement("ALTER TABLE users MODIFY role ENUM($enumList) DEFAULT '" . UserRole::getByVersion('v1') . "'");
    }
};
