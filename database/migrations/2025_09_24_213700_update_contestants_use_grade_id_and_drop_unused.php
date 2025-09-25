<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('contestants', function (Blueprint $table) {
            if (!Schema::hasColumn('contestants', 'grade_id')) {
                $table->foreignId('grade_id')->nullable()->after('school_id')->constrained('grades')->nullOnDelete();
            }
        });

        // Migrate existing textual grade to grades table and set grade_id
        if (Schema::hasColumn('contestants', 'grade')) {
            // Ensure grades created for distinct grade names
            $distinctGrades = DB::table('contestants')->select('grade')->whereNotNull('grade')->distinct()->pluck('grade');
            foreach ($distinctGrades as $g) {
                $gradeId = DB::table('grades')->where('name', $g)->value('id');
                if (!$gradeId) {
                    $gradeId = DB::table('grades')->insertGetId(['name' => $g, 'created_at' => now(), 'updated_at' => now()]);
                }
                DB::table('contestants')->where('grade', $g)->update(['grade_id' => $gradeId]);
            }
        }

        // Drop unused columns
        Schema::table('contestants', function (Blueprint $table) {
            if (Schema::hasColumn('contestants', 'born_date')) {
                $table->dropColumn('born_date');
            }
            if (Schema::hasColumn('contestants', 'education_level_id')) {
                $table->dropConstrainedForeignId('education_level_id');
            }
            if (Schema::hasColumn('contestants', 'grade')) {
                $table->dropColumn('grade');
            }
        });
    }

    public function down(): void
    {
        // Recreate dropped columns
        Schema::table('contestants', function (Blueprint $table) {
            if (!Schema::hasColumn('contestants', 'born_date')) {
                $table->date('born_date')->nullable()->after('ci_document');
            }
            if (!Schema::hasColumn('contestants', 'education_level_id')) {
                $table->foreignId('education_level_id')->nullable()->constrained('levels')->nullOnDelete()->after('department');
            }
            if (!Schema::hasColumn('contestants', 'grade')) {
                $table->string('grade')->nullable()->after('education_level_id');
            }
        });

        // Restore textual grade from grade_id
        if (Schema::hasColumn('contestants', 'grade_id')) {
            $rows = DB::table('contestants')->select('id', 'grade_id')->whereNotNull('grade_id')->get();
            foreach ($rows as $row) {
                $name = DB::table('grades')->where('id', $row->grade_id)->value('name');
                DB::table('contestants')->where('id', $row->id)->update(['grade' => $name]);
            }
            Schema::table('contestants', function (Blueprint $table) {
                $table->dropConstrainedForeignId('grade_id');
            });
        }
    }
};
