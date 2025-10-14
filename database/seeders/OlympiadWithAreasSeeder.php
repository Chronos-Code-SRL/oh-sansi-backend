<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Olympiad;
use App\Models\Area;
use App\Models\OlympiadArea;

class OlympiadWithAreasSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create first Olympiad with all areas
        $olympiad1 = Olympiad::firstOrCreate([
            'name' => 'Olimpiada Científica',
            //'edition' => '2025',
        ], [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonth()->toDateString(),
        ]);

        // Map all existing areas to this olympiad
        $areas = Area::all();
        foreach ($areas as $area) {
            OlympiadArea::firstOrCreate([
                'olympiad_id' => $olympiad1->id,
                'area_id' => $area->id,
            ]);
        }

        $this->command->info('Olympiad 1 created with ID: '.$olympiad1->id.' and all areas mapped.');

        // Create second Olympiad with only 4 specific areas
        $olympiad2 = Olympiad::firstOrCreate([
            'name' => 'Olimpiada Nacional de Ciencias',
            //'edition' => '2025',
        ], [
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(2)->toDateString(),
        ]);

        // Map only first 4 areas to this olympiad
        $firstFourAreas = Area::limit(4)->get();
        foreach ($firstFourAreas as $area) {
            OlympiadArea::firstOrCreate([
                'olympiad_id' => $olympiad2->id,
                'area_id' => $area->id,
            ]);
        }

        $this->command->info('Olympiad 2 created with ID: '.$olympiad2->id.' with 4 areas mapped: '.implode(', ', $firstFourAreas->pluck('name')->toArray()));
    }
}
