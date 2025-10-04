<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Add phone and email to tutors if missing
        Schema::table('tutors', function (Blueprint $table) {
            if (!Schema::hasColumn('tutors', 'phone')) {
                $table->string('phone')->nullable()->after('last_name');
            }
            if (!Schema::hasColumn('tutors', 'email')) {
                $table->string('email')->nullable()->after('phone');
            }
        });

        // Add gender to contestants if missing
        Schema::table('contestants', function (Blueprint $table) {
            if (!Schema::hasColumn('contestants', 'gender')) {
                $table->string('gender')->nullable()->after('lastname');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tutors', function (Blueprint $table) {
            if (Schema::hasColumn('tutors', 'email')) {
                $table->dropColumn('email');
            }
            if (Schema::hasColumn('tutors', 'phone')) {
                $table->dropColumn('phone');
            }
        });

        Schema::table('contestants', function (Blueprint $table) {
            if (Schema::hasColumn('contestants', 'gender')) {
                $table->dropColumn('gender');
            }
        });
    }
};
