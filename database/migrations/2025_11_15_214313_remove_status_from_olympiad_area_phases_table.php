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
        Schema::table('olympiad_area_phases', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olympiad_area_phases', function (Blueprint $table) {
            $table->enum('status', ['Sin empezar', 'Activa', 'Terminada'])->default('Sin empezar')->after('phase_id');
        });
    }
};
