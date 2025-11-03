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
        Schema::table('user_areas', function (Blueprint $table) {
            $table->foreignId('olympiad_id')->constrained('olympiads')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_areas', function (Blueprint $table) {
            $table->dropColumn('olympiad_id');
        });
    }
};
