<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Grade;
use App\Models\Area;
use App\Models\Olympiad;
use App\Models\User;
use App\Models\Phase;
use App\Models\Level;
use App\Models\LevelGrade;
use App\Models\OlympiadAreaPhaseLevelGrade;
use Illuminate\Support\Facades\DB;

class TheOrigin extends Seeder
{
    public function run(): void
    {
        //Createt Grades
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

        // Create Olympiad
        $olympiadData = [
            'name' => 'Olimpiadas Verano 2025',
            'start_date' => '2025-11-01',
            'end_date' => '2025-11-28',
            'number_of_phases' => 2,
            'status' => 'Activa',
            'default_score_cut' => 51,
            'default_max_score' => 100,
        ];
        $Olympiad = Olympiad::firstOrCreate($olympiadData);

        // Associate areas with Olympiad
        $olympiadAreas = ['Astronomía', 'Biología', 'Física', 'Informática', 'Matemática', 'Química', 'Robótica', 'Astrofísica'];

        foreach ($olympiadAreas as $areaName) {
            $area = Area::where('name', $areaName)->first();
            if ($area) {
                DB::table('olympiad_areas')->updateOrInsert([
                    'olympiad_id' => $Olympiad->id,
                    'area_id' => $area->id,
                ]);
            }
        }

        //Create olympics phases
        for ($i = 1; $i <= $Olympiad->number_of_phases; $i++) {
            Phase::firstOrCreate([
                'order' => $i,
            ], [
                'name' => "Fase $i",
            ]);
        }

        // Associate phases with Olympiad Areas
        $phases = Phase::all();

        foreach ($olympiadAreas as $areaName) {
            $area = Area::where('name', $areaName)->first();

            $olympiadArea = DB::table('olympiad_areas')
                ->where('olympiad_id', $Olympiad->id)
                ->where('area_id', $area->id)
                ->first();

            if ($olympiadArea) {
                foreach ($phases as $phase) {
                    DB::table('olympiad_area_phases')->updateOrInsert([
                        'olympiad_area_id' => $olympiadArea->id,
                        'phase_id' => $phase->id,
                    ]);
                }
            }
        }

        // Create Admin User
        $adminUser = User::firstOrCreate([
            'first_name' => 'Root',
            'last_name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('password'),
            'ci' => '12345678A',
            'phone_number' => '71780589',
            'genre' => 'femenino',
            // 'roles_id' => 1
        ]);

        $adminRole = DB::table('user_roles')->updateOrInsert([
            'user_id' => $adminUser->id,
            'role_id' => 1,
        ]);

        //Create Responsible Academic User
        $academicUser = User::firstOrCreate([
            'first_name' => 'Beomgyu',
            'last_name' => 'Choi',
            'email' => 'beomgyu@gmail.com',
            'password' => bcrypt('BC44444444'),
            'ci' => '44444444',
            'phone_number' => '12345678',
            'genre' => 'masculino',
            // 'roles_id' => 2,
            'profesion' => 'INGENIERO EN CIVIL',
        ]);

        $academicUserRole = DB::table('user_roles')->updateOrInsert([
            'user_id' => $academicUser->id,
            'role_id' => 2,
        ]);

        $academicUserRoleId = DB::table('user_roles')
            ->where('user_id', $academicUser->id)
            ->where('role_id', 2)
            ->value('id');

        $academicAreas = ['1', '2', '3', '4', '5', '6', '7', '8'];
        $OlympiadId = 1;

        foreach ($academicAreas as $areaId) {
            DB::table('user_area_olympiads')->updateOrInsert([
                'user_role_id' => $academicUserRoleId,
                'area_id' => $areaId,
                'olympiad_id' => $OlympiadId,
            ]);
        }

        //Create Evaluator User
        $evaluatorUser = User::firstOrCreate([
            'first_name' => 'Soobin',
            'last_name' => 'Choi',
            'email' => 'soobin@gmail.com',
            'password' => bcrypt('SC55555555'),
            'ci' => '55555555',
            'phone_number' => '12345678',
            'genre' => 'masculino',
            // 'role_id' => 3,
            'profesion' => 'INGENIERO EN INFORMATICA',
        ]);

        $evaluatorUserRole = DB::table('user_roles')->updateOrInsert([
            'user_id' => $evaluatorUser->id,
            'role_id' => 3,
        ]);

        $evaluatorUserRoleId = DB::table('user_roles')
            ->where('user_id', $evaluatorUser->id)
            ->where('role_id', 3)
            ->value('id');

        $evaluatorAreas = ['1', '2', '3', '4', '5', '6', '7', '8'];

        foreach ($evaluatorAreas as $areaId) {
            DB::table('user_area_olympiads')->updateOrInsert([
                'user_role_id' => $evaluatorUserRoleId,
                'area_id' => $areaId,
                'olympiad_id' => $OlympiadId,
            ]);
        }

        $areaLevels = [
            'Informática' => [
                'GUACAMAYO' => ['Quinto de primaria', 'Sexto de primaria'],
                'BUFEO' => ['Primero de secundaria', 'Segundo de secundaria', 'Tercero de secundaria'],
                'PUMA' => ['Cuarto de secundaria', 'Quinto de secundaria', 'Sexto de secundaria'],
            ],

            'Astronomía' => [
                'TERCERO PRIMARIA' => ['Tercero de primaria'],
                'CUARTO PRIMARIA' => ['Cuarto de primaria'],
                'QUINTO PRIMARIA' => ['Quinto de primaria'],
                'SEXTO PRIMARIA' => ['Sexto de primaria'],
                'PRIMERO SECUNDARIA' => ['Primero de secundaria'],
                'SEGUNDO SECUNDARIA' => ['Segundo de secundaria'],
                'TERCERO SECUNDARIA' => ['Tercero de secundaria'],
                'CUARTO SECUNDARIA' => ['Cuarto de secundaria'],
                'QUINTO SECUNDARIA' => ['Quinto de secundaria'],
                'SEXTO SECUNDARIA' => ['Sexto de secundaria'],
            ],

            'Matemática' => [
                'PRIMERO SECUNDARIA' => ['Primero de secundaria'],
                'SEGUNDO SECUNDARIA' => ['Segundo de secundaria'],
                'TERCERO SECUNDARIA' => ['Tercero de secundaria'],
                'CUARTO SECUNDARIA' => ['Cuarto de secundaria'],
                'QUINTO SECUNDARIA' => ['Quinto de secundaria'],
                'SEXTO SECUNDARIA' => ['Sexto de secundaria'],
            ],

            'Química' => [
                'SEGUNDO SECUNDARIA' => ['Segundo de secundaria'],
                'TERCERO SECUNDARIA' => ['Tercero de secundaria'],
                'CUARTO SECUNDARIA' => ['Cuarto de secundaria'],
                'QUINTO SECUNDARIA' => ['Quinto de secundaria'],
                'SEXTO SECUNDARIA' => ['Sexto de secundaria'],
            ],

            'Física' => [
                'CUARTO SECUNDARIA' => ['Cuarto de secundaria'],
                'QUINTO SECUNDARIA' => ['Quinto de secundaria'],
                'SEXTO SECUNDARIA' => ['Sexto de secundaria'],
            ],

            'Biología' => [
                'SEGUNDO SECUNDARIA' => ['Segundo de secundaria'],
                'TERCERO SECUNDARIA' => ['Tercero de secundaria'],
                'CUARTO SECUNDARIA' => ['Cuarto de secundaria'],
                'QUINTO SECUNDARIA' => ['Quinto de secundaria'],
                'SEXTO SECUNDARIA' => ['Sexto de secundaria'],
            ],

            'Robótica' => [
                'BUILDERS' => ['Quinto de primaria', 'Sexto de primaria'],
                'LEGO' => [
                    'Primero de secundaria',
                    'Segundo de secundaria',
                    'Tercero de secundaria',
                    'Cuarto de secundaria',
                    'Quinto de secundaria',
                    'Sexto de secundaria'
                ],
            ],
        ];

        // recording the levels
        foreach ($areaLevels as $areaName => $levels) {
            $area = Area::where('name', $areaName)->firstOrFail();

            $olympiadArea = DB::table('olympiad_areas')
                ->where('olympiad_id', $Olympiad->id)
                ->where('area_id', $area->id)
                ->first();

            foreach ($levels as $levelName => $gradeNames) {
                // Create or restore the level
                $level = Level::firstOrCreate(['name' => $levelName]);

                // Go through the grades that belong to this level
                foreach ($gradeNames as $gradeName) {
                    $grade = Grade::where('name', $gradeName)->firstOrFail();

                    // Create level–grade–area relationship
                    $levelGrade = LevelGrade::firstOrCreate([
                        'level_id' => $level->id,
                        'grade_id' => $grade->id,
                        'olympiad_area_id' => $olympiadArea->id,
                    ]);
                }

                // Now we link the level with the phases
                $phases = Phase::all();
                foreach ($phases as $phase) {
                    // Search for the olympiad_area_phase relationship
                    $olympiadAreaPhase = DB::table('olympiad_area_phases')
                        ->where('olympiad_area_id', $olympiadArea->id)
                        ->where('phase_id', $phase->id)
                        ->first();

                    if ($olympiadAreaPhase) {
                        // Link all level_grades of this level and area to this phase
                        $levelGrades = LevelGrade::where('level_id', $level->id)
                            ->where('olympiad_area_id', $olympiadArea->id)
                            ->get();

                        foreach ($levelGrades as $lg) {
                            DB::table('olympiad_area_phase_level_grades')->updateOrInsert([
                                'olympiad_area_phase_id' => $olympiadAreaPhase->id,
                                'level_grade_id' => $lg->id,
                            ], [
                                'score_cut' => 51,
                                'max_score' => 100,
                                // 'status' => 'Sin empezar',
                            ]);
                        }
                    }
                }
            }
        }
    }
}
