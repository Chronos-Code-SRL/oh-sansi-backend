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
        Schema::create('contestant_level_grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contestant_id')->constrained('contestants')->onDelete('cascade');
            $table->foreignId('level_grade_id')->constrained('level_grades')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contestant_level_grades');
    }
};
