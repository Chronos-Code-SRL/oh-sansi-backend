<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Area;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
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

        // Create Areas
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


        $adminUser = User::firstOrCreate([
            'first_name' => 'Root',
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'ci' => '1111111',
            'phone_number' => '71780589',
            'genre' => 'femenino',
        ]);

        $adminRole = DB::table('user_roles')->updateOrInsert([
            'user_id' => $adminUser->id,
            'role_id' => 1,
        ]);

        $this->command->info('Grades, Areas and user admin created successfully.');

    }
}
