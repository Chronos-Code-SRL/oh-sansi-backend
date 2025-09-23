<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contestants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname');
            $table->string('ci_document')->unique();
            $table->date('born_date')->nullable();
            $table->foreignId('tutor_id')->nullable()->constrained('tutors')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->enum('city', [
                'Beni','Chuquisaca','Cochabamba','La_Paz','Oruro','Pando','Potosí','Santa_Cruz','Tarija'
            ])->nullable();
            $table->foreignId('education_level_id')->nullable()->constrained('levels')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contestants');
    }
};


