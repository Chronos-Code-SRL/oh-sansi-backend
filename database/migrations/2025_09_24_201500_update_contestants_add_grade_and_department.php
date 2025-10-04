<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Add department if missing, and migrate from city if exists, then drop city
        if (!Schema::hasColumn('contestants', 'department')) {
            Schema::table('contestants', function (Blueprint $table) {
                $table->string('department')->nullable()->after('school_id');
            });
        }
        if (Schema::hasColumn('contestants', 'city')) {
            // Copy values from city to department where department is null
            DB::statement('UPDATE contestants SET department = COALESCE(department, city)');
            Schema::table('contestants', function (Blueprint $table) {
                $table->dropColumn('city');
            });
        }

        // Add grade (textual grade of schooling) if missing
        if (!Schema::hasColumn('contestants', 'grade')) {
            Schema::table('contestants', function (Blueprint $table) {
                $table->string('grade')->nullable()->after('education_level_id');
            });
        }
    }

    public function down(): void
    {
        // Recreate city if needed and move department back
        if (!Schema::hasColumn('contestants', 'city')) {
            Schema::table('contestants', function (Blueprint $table) {
                $table->string('city')->nullable()->after('school_id');
            });
        }
        if (Schema::hasColumn('contestants', 'department')) {
            DB::statement('UPDATE contestants SET city = COALESCE(city, department)');
            Schema::table('contestants', function (Blueprint $table) {
                $table->dropColumn('department');
            });
        }

        if (Schema::hasColumn('contestants', 'grade')) {
            Schema::table('contestants', function (Blueprint $table) {
                $table->dropColumn('grade');
            });
        }
    }
};
