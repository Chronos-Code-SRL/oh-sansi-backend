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
        Schema::table('evaluations', function (Blueprint $table) {
            $table->enum('classification_status', ['clasificado', 'desclasificado', 'descalificado'])
                  ->nullable()
                  ->after('status');
            $table->enum('classification_place', ['Oro', 'Plata', 'Bronce', 'Mención honorífica'])
                  ->nullable()
                  ->after('classification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('evaluations', function (Blueprint $table) {
            $table->dropColumn(['classification_status', 'classification_place']);
        });
    }
};
