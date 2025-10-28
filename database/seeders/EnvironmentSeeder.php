<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\User;
use App\Models\Area;

class EnvironmentSeeder extends Seeder
{
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
            Area::firstOrCreate($area);
        }

        $user = new User();
        $user->first_name = 'Root';
        $user->last_name = 'Admin';
        $user->email = 'admin@gmail.com';
        $user->password = bcrypt('password');
        $user->ci = '12345678';
        $user->phone_number = '+59 71780589';
        $user->genre = 'femenino';
        $user->roles_id = 1;
        $user->save();

        $this->command->info('Grades, Areas and user admin created successfully.');
    }
}
