<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OwenIt\Auditing\Models\Audit;
use App\Models\Evaluation;

class TestAuditEndpoints extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:audit-endpoints';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test audit endpoints functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Audit Endpoints...');

        // Test basic audit queries
        $totalAudits = Audit::where('auditable_type', 'App\\Models\\Evaluation')->count();
        $this->info("Total evaluation audits in database: {$totalAudits}");

        if ($totalAudits === 0) {
            $this->warn('No audit records found. Run "php artisan test:evaluation-audit" first.');
            return;
        }

        // Test getting audits with evaluation data
        $audits = Audit::where('auditable_type', 'App\\Models\\Evaluation')
                      ->with(['auditable'])
                      ->limit(5)
                      ->get();

        $this->info('Sample audit records:');
        foreach ($audits as $audit) {
            $this->line("- Audit ID: {$audit->id}, Event: {$audit->event}, User ID: {$audit->user_id}");
            $this->line("  Evaluation ID: {$audit->auditable_id}");
            $this->line("  Changes: " . json_encode($audit->new_values));
        }

        // Test evaluation with relationships
        $evaluationWithData = Evaluation::with([
            'registration.contestant',
            'olympiadAreaPhase.olympiadArea.olympiad',
            'olympiadAreaPhase.olympiadArea.area',
            'olympiadAreaPhase.phase'
        ])->first();

        if ($evaluationWithData) {
            $this->info('Sample evaluation with full relationships:');
            $this->line("- Evaluation ID: {$evaluationWithData->id}");
            $contestant = $evaluationWithData->registration->contestant ?? null;
            if ($contestant) {
                $this->line("- Contestant: {$contestant->first_name} {$contestant->last_name}");
            }

            $olympiad = $evaluationWithData->olympiadAreaPhase->olympiadArea->olympiad ?? null;
            if ($olympiad) {
                $this->line("- Olympiad: {$olympiad->name}");
            }
        }

        $this->info('Audit system is working correctly!');
        $this->info('You can now test the API endpoints with a REST client.');
    }
}
