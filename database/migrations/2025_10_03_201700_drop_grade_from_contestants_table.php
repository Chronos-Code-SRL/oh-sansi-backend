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
        Schema::table('contestants', function (Blueprint $table) {
            if (Schema::hasColumn('contestants', 'grade')) {
                $table->dropColumn('grade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contestants', function (Blueprint $table) {
            $table->string('grade', 50)->nullable();
        });
    }
};
