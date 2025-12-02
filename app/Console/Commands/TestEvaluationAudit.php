<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Evaluation;
use App\Models\User;
use OwenIt\Auditing\Models\Audit;
use Illuminate\Support\Facades\Auth;

class TestEvaluationAudit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:evaluation-audit';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the evaluation audit system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Evaluation Audit System...');

        // Get first evaluation and user for testing
        $evaluation = Evaluation::first();
        $user = User::first();

        if (!$evaluation) {
            $this->error('No evaluations found. Please create some evaluations first.');
            return;
        }

        if (!$user) {
            $this->error('No users found. Please create a user first.');
            return;
        }

        $this->info("Testing with Evaluation ID: {$evaluation->id}");
        $this->info("Testing with User: {$user->first_name} {$user->last_name}");

        // Simulate authentication
        Auth::login($user);

        // Make some changes to test auditing
        $oldScore = $evaluation->score;
        $evaluation->score = ($oldScore ?? 0) + 10;
        $evaluation->description = 'Test audit update at ' . now();
        $evaluation->save();

        $this->info('Updated evaluation score and description');

        // Check if audit was created
        $latestAudit = Audit::where('auditable_type', 'App\\Models\\Evaluation')
                           ->where('auditable_id', $evaluation->id)
                           ->latest()
                           ->first();

        if ($latestAudit) {
            $this->info('Audit record created successfully!');
            $this->line('Audit ID: ' . $latestAudit->id);
            $this->line('Event: ' . $latestAudit->event);
            $this->line('User ID: ' . $latestAudit->user_id);
            $this->line('Old Values: ' . json_encode($latestAudit->old_values));
            $this->line('New Values: ' . json_encode($latestAudit->new_values));
        } else {
            $this->error('No audit record found. Something went wrong.');
        }

        // Test classification status update
        $evaluation->classification_status = 'clasificado';
        $evaluation->classification_place = 'Oro';
        $evaluation->save();

        $this->info('Updated classification status and place');

        // Check total audit count for this evaluation
        $totalAudits = Audit::where('auditable_type', 'App\\Models\\Evaluation')
                           ->where('auditable_id', $evaluation->id)
                           ->count();

        $this->info("✅ Total audit records for this evaluation: {$totalAudits}");

        $this->info('Test completed successfully!');
    }
}
