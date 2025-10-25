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
        Schema::create('csv_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->string('original_file_name');
            $table->integer('successful_records')->default(0);
            $table->integer('failed_records')->default(0);
            $table->integer('total_records')->default(0);
            $table->string('file_path');
            $table->string('error_file_path')->nullable();
            $table->integer('file_size')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('csv_uploads');
    }
};
