<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Make first_name and last_name nullable in tutors (PostgreSQL-safe)
        DB::statement('ALTER TABLE tutors ALTER COLUMN first_name DROP NOT NULL');
        DB::statement('ALTER TABLE tutors ALTER COLUMN last_name DROP NOT NULL');
    }

    public function down(): void
    {
        // Revert: set NULLs to empty string, then enforce NOT NULL
        DB::statement("UPDATE tutors SET first_name = '' WHERE first_name IS NULL");
        DB::statement("UPDATE tutors SET last_name = '' WHERE last_name IS NULL");
        DB::statement('ALTER TABLE tutors ALTER COLUMN first_name SET NOT NULL');
        DB::statement('ALTER TABLE tutors ALTER COLUMN last_name SET NOT NULL');
    }
};
