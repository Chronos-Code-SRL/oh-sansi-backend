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
        Schema::create('contestants', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 50);
            $table->string('last_name', 50);
            $table->string('ci_document', 13)->unique();
            $table->char('gender', 1);
            $table->string('school_name', 100);
            $table->string('department', 50);
            $table->string('phone_number', 8)->nullable();
            $table->string('email', 100)->nullable()->unique();
            $table->string('tutor_name', 50);
            $table->string('tutor_number', 8);
            $table->string('grade', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contestants');
    }
};
