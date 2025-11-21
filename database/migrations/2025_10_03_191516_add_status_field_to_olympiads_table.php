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
        Schema::table('olympiads', function (Blueprint $table) {
            $table->enum('status', ['En planificación', 'Activa', 'Terminada'])->default('En planificación');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('olympiads', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
