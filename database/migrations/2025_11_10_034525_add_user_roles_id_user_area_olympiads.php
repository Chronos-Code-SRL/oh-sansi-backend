<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_area_olympiads', function (Blueprint $table) {
            $table->foreignId('user_role_id')->constrained('user_roles')->onDelete('cascade');;
        });
    }

    public function down(): void
    {
        Schema::table('user_area_olympiads', function (Blueprint $table) {
            $table->dropColumn('user_role_id');
        });
    }
};
