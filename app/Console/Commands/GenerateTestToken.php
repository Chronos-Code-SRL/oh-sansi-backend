<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class GenerateTestToken extends Command
{
    protected $signature = 'test:generate-token';
    protected $description = 'Generate a test token for audit API testing';

    public function handle()
    {
        $user = User::first();

        if (!$user) {
            $this->error('No users found in database');
            return;
        }

        $token = $user->createToken('test-audit')->plainTextToken;

        $this->info("User: {$user->first_name} {$user->last_name} (ID: {$user->id})");
        $this->info("Token: {$token}");
        $this->info('');
        $this->info('Use this token in your API requests:');
        $this->info("Authorization: Bearer {$token}");
    }
}
