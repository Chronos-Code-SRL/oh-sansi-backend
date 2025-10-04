<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $areas = [
            ['name' => 'Astronomía'],
            ['name' => 'Biología'],
            ['name' => 'Física'],
            ['name' => 'Informática'],
            ['name' => 'Matemática'],
            ['name' => 'Química'],
            ['name' => 'Robótica'],
            ['name' => 'Astrofísica']
        ];

        foreach ($areas as $area) {
            \App\Models\Area::create($area);
        }
    }
}
