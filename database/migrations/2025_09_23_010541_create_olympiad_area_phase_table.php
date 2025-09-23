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
        Schema::create('olympiad_area_phase', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olympiad_area_id')->constrained('olympiad_area')->onDelete('cascade');
            $table->foreignId('phase_id')->constrained('phases')->onDelete('cascade');
            $table->integer('score_cut')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_area_phase');
    }
};
