<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class OlympiadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $olympiad = \App\Models\Olympiad::create([
            'name' => 'Olimpiada Científica Estudiantil',
            'edition' => '2025',
            'start_date' => '2025-01-01',
            'end_date' => '2025-12-31'
        ]);

        // Create olympiad areas for all existing areas
        $areas = \App\Models\Area::all();
        foreach ($areas as $area) {
            \App\Models\OlympiadArea::create([
                'olympiad_id' => $olympiad->id,
                'area_id' => $area->id
            ]);
        }
    }
}
