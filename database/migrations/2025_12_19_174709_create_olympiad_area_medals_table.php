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
        Schema::create('olympiad_area_medals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olympiad_area_id')->constrained('olympiad_areas')->onDelete('cascade');
            $table->integer('gold');
            $table->integer('silver');
            $table->integer('bronze');
            $table->integer('honorable_mention');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_area_medals');
    }
};
