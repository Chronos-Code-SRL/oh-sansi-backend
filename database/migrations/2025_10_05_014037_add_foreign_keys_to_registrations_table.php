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
            // Add foreign key constraint for grade_id
            $table->foreign('grade_id')->references('id')->on('grades')->onDelete('set null');

            // Add foreign key constraint for level_id
            $table->foreign('level_id')->references('id')->on('levels')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registrations', function (Blueprint $table) {
            // Drop foreign key constraints
            $table->dropForeign(['grade_id']);
            $table->dropForeign(['level_id']);
        });
    }
};
