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
        Schema::table('level_grades', function (Blueprint $table) {
            // Ensure id field exists (it should already be there from original migration)
            if (!Schema::hasColumn('level_grades', 'id')) {
                $table->id();
            }

            // Add olympiad_area_id if it doesn't exist
            if (!Schema::hasColumn('level_grades', 'olympiad_area_id')) {
                $table->foreignId('olympiad_area_id')->constrained('olympiad_areas')->onDelete('cascade');
            }

            // Ensure level_id and grade_id exist (they should already be there from original migration)
            if (!Schema::hasColumn('level_grades', 'level_id')) {
                $table->foreignId('level_id')->constrained('levels')->onDelete('cascade');
            }

            if (!Schema::hasColumn('level_grades', 'grade_id')) {
                $table->foreignId('grade_id')->constrained('grades')->onDelete('cascade');
            }

            // Update unique constraint to include olympiad_area_id
            //$table->unique(['olympiad_area_id', 'level_id', 'grade_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('level_grades', function (Blueprint $table) {
            // Drop the updated unique constraint first
            $table->dropUnique(['olympiad_area_id', 'level_id', 'grade_id']);

            // Restore original unique constraint
            $table->unique(['level_id', 'grade_id']);

            // Drop olympiad_area_id if it exists
            if (Schema::hasColumn('level_grades', 'olympiad_area_id')) {
                $table->dropForeign(['olympiad_area_id']);
                $table->dropColumn('olympiad_area_id');
            }
        });
    }
};
