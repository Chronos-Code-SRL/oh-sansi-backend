<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Level;

class LevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $levels = [
            ['name' => 'Booster'],
            ['name' => 'Buffeo'],
            ['name' => 'Master'],
            ['name' => 'Primero'],
            ['name' => 'Segundo'],
            ['name' => 'Tercero'],
            ['name' => 'Cuarto'],
            ['name' => 'Quinto'],
            ['name' => 'Sexto']
        ];

        foreach ($levels as $level) {
            Level::firstOrCreate($level);
        }

        $this->command->info('Levels created successfully.');
    }
}
