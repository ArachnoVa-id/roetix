<?php

// use Illuminate\Database\Migrations\Migration;
// use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\Schema;

// return new class extends Migration
// {
//     public function up(): void
//     {
//         // Skip creating sections table since it already exists
//         if (!Schema::hasTable('sections')) {
//             Schema::create('sections', function (Blueprint $table) {
//                 $table->string('id')->primary();
//                 $table->string('name');
//                 $table->timestamps();
//             });
//         }

//         // Add section_id to seats table if it doesn't exist
//         Schema::table('seats', function (Blueprint $table) {
//             if (!Schema::hasColumn('seats', 'section_id')) {
//                 $table->string('section_id')->nullable();
//                 $table->foreign('section_id')->references('id')->on('sections');
//             }
//         });
//     }

//     public function down(): void
//     {
//         Schema::table('seats', function (Blueprint $table) {
//             $table->dropForeign(['section_id']);
//             $table->dropColumn('section_id');
//         });
//     }
// };