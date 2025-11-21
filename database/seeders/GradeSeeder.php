<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Grade;

class GradeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grades = [
            // Primaria
            ['name' => 'Primero de primaria'],
            ['name' => 'Segundo de primaria'],
            ['name' => 'Tercero de primaria'],
            ['name' => 'Cuarto de primaria'],
            ['name' => 'Quinto de primaria'],
            ['name' => 'Sexto de primaria'],

            // Secundaria
            ['name' => 'Primero de secundaria'],
            ['name' => 'Segundo de secundaria'],
            ['name' => 'Tercero de secundaria'],
            ['name' => 'Cuarto de secundaria'],
            ['name' => 'Quinto de secundaria'],
            ['name' => 'Sexto de secundaria']
        ];

        foreach ($grades as $grade) {
            Grade::firstOrCreate($grade);
        }

        $this->command->info('Grades created successfully.');
    }
}
