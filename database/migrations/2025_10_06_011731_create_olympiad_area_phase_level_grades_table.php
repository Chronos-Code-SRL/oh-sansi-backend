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
        Schema::create('olympiad_area_phase_level_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olympiad_area_phase_id')->constrained('olympiad_area_phases')->onDelete('cascade');
            $table->foreignId('olympiad_area_level_grade_id')->constrained('olympiad_area_level_grades')->onDelete('cascade');
            $table->integer('score_cut');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_area_phase_level_grades');
    }
};
