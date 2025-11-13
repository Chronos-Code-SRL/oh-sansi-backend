<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Contestant;
use App\Models\Registration;
use App\Models\ContestantLevelGrade;
use App\Models\Evaluation;
use App\Models\Olympiad;
use App\Models\OlympiadArea;
use App\Models\LevelGrade;
use App\Models\OlympiadAreaPhase;
use App\Models\Phase;
use App\Models\Level;
use App\Models\Grade;

class RankedContestantsSeeder extends Seeder
{
    public function run(): void
    {
        // Find an olympiad
        $olympiad = Olympiad::first();
        if (!$olympiad) {
            $this->command->error('No olympiad found. Run other seeders first.');
            return;
        }

        // Get an olympiad area for that olympiad
        $olympiadArea = OlympiadArea::where('olympiad_id', $olympiad->id)->first();
        if (!$olympiadArea) {
            $this->command->error('No olympiad_area found for olympiad '.$olympiad->id);
            return;
        }

        // Get a phase for that olympiad area; create one if none exists
        $oap = OlympiadAreaPhase::where('olympiad_area_id', $olympiadArea->id)->first();
        if (!$oap) {
            // Ensure there is at least one Phase
            $phase = Phase::first();
            if (!$phase) {
                $phase = Phase::create(['order' => 1, 'name' => 'Fase 1']);
            }

            // Create an olympiad_area_phase linking area and phase
            $oap = OlympiadAreaPhase::create([
                'olympiad_area_id' => $olympiadArea->id,
                'phase_id' => $phase->id,
            ]);

            $this->command->info('Created olympiad_area_phase id='.$oap->id.' for olympiad_area '.$olympiadArea->id);
        }

        // Choose a level_grade existing for this olympiad_area, create minimal one if missing
        $levelGrade = LevelGrade::where('olympiad_area_id', $olympiadArea->id)->first();
        if (!$levelGrade) {
            // Ensure there's at least one Level and Grade
            $level = Level::first();
            if (!$level) {
                $level = Level::create(['name' => 'TEST LEVEL']);
            }

            $grade = Grade::first();
            if (!$grade) {
                $grade = Grade::create(['name' => 'TEST GRADE']);
            }

            $levelGrade = LevelGrade::create([
                'olympiad_area_id' => $olympiadArea->id,
                'level_id' => $level->id,
                'grade_id' => $grade->id,
            ]);

            $this->command->info('Created fallback LevelGrade id='.$levelGrade->id.' for olympiad_area '.$olympiadArea->id);
        }

        // Determine score_cut if present
        $scoreCut = DB::table('olympiad_area_phase_level_grades')
            ->where('olympiad_area_phase_id', $oap->id)
            ->where('level_grade_id', $levelGrade->id)
            ->value('score_cut');

        if (is_null($scoreCut)) {
            $scoreCut = $olympiad->default_score_cut ?? 51;
        }

        $cases = [
            ['first_name' => 'Ana', 'last_name' => 'Di Maria', 'score' => $scoreCut + 5, 'description' => null],
            ['first_name' => 'Juan', 'last_name' => 'Pendragon Simpson', 'score' => max(0, $scoreCut - 5), 'description' => null],
            ['first_name' => 'Lucia', 'last_name' => 'Terceros Nobel', 'score' => null, 'description' => null],
            ['first_name' => 'Pedro', 'last_name' => 'Pica Piedra', 'score' => 80, 'description' => 'Observación especial: irregularidad'],
        ];

        foreach ($cases as $c) {
            // Create contestant (avoid duplicates by ci_document)
            $ci = 'TEST-'.strtoupper(substr($c['first_name'],0,1)).rand(1000,9999);

            $contestant = Contestant::create([
                'first_name' => $c['first_name'],
                'last_name' => $c['last_name'],
                // use short gender code to match schema (e.g. 'M' or 'F')
                'gender' => 'M',
                'ci_document' => $ci,
                'school_name' => 'Harvard',
                'department' => 'Santa Cruz',
                'tutor_name' => 'Daniel Traviezo',
                'tutor_number' => '00000000'
            ]);

            // Create registration
            $registration = Registration::create([
                'contestant_id' => $contestant->id,
                'olympiad_area_id' => $olympiadArea->id
            ]);

            // Link contestant with level_grade
            ContestantLevelGrade::create([
                'contestant_id' => $contestant->id,
                'level_grade_id' => $levelGrade->id
            ]);

            // Create evaluation
            Evaluation::create([
                'score' => $c['score'],
                'description' => $c['description'],
                'registration_id' => $registration->id,
                'olympiad_area_phase_id' => $oap->id,
                'status' => 1
            ]);

            $this->command->info("Created test contestant {$contestant->first_name} {$contestant->last_name}");
        }

        $this->command->info('Ranked contestants seeded (score_cut = '.$scoreCut.')');
    }
}
