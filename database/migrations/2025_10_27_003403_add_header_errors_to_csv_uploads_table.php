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
        Schema::table('csv_uploads', function (Blueprint $table) {
            $table->integer('header_errors')->default(0)->after('failed_records');
            $table->integer('competitor_errors')->default(0)->after('header_errors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('csv_uploads', function (Blueprint $table) {
            $table->dropColumn(['header_errors', 'competitor_errors']);
        });
    }
};
