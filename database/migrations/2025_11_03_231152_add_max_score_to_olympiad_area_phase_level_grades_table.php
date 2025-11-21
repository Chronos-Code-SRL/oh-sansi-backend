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
        Schema::table('olympiad_area_phase_level_grades', function (Blueprint $table) {
            $table->integer('max_score')->nullable()->after('score_cut');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olympiad_area_phase_level_grades', function (Blueprint $table) {
            $table->dropColumn('max_score');
        });
    }
};
