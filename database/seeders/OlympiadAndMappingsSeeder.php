<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Olympiad;
use App\Models\Area;
use App\Models\OlympiadArea;

class OlympiadAndMappingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create a sample Olympiad if none exists
        $olympiad = Olympiad::firstOrCreate([
            'name' => 'Olimpiada Científica',
            'edition' => '2025',
        ], [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        // Map all existing areas to this olympiad
        $areas = Area::all();
        foreach ($areas as $area) {
            OlympiadArea::firstOrCreate([
                'olympiad_id' => $olympiad->id,
                'area_id' => $area->id,
            ]);
        }

        $this->command->info('Olympiad created with ID: '.$olympiad->id.' and all areas mapped.');
    }
}
