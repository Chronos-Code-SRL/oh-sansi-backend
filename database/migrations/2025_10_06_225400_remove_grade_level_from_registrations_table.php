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
        Schema::table('registrations', function (Blueprint $table) {
            if (Schema::hasColumn('registrations', 'grade_id')) {
                $table->dropForeign(['grade_id']);
                $table->dropColumn('grade_id');
            }
            if (Schema::hasColumn('registrations', 'level_id')) {
                $table->dropForeign(['level_id']);
                $table->dropColumn('level_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            $table->unsignedBigInteger('grade_id')->nullable()->after('olympiad_area_id');
            $table->unsignedBigInteger('level_id')->nullable()->after('grade_id');
        });
    }
};
