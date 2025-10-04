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
        if (Schema::hasTable('groups')) {
            Schema::drop('groups');
        }
        if (Schema::hasTable('grade_levels')) {
            Schema::drop('grade_levels');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: tables intentionally removed as per new schema
    }
};
