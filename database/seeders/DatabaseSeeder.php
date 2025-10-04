<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $user = new User();
        $user->first_name = 'Admin';
        $user->last_name = 'User';
        $user->email = 'admin@example.com';
        $user->password = bcrypt('password');
        $user->ci = '12345678';
        $user->phone_number = '+59 12345678';
        $user->genre = 'femenino';
        $user->roles_id = 1;
        $user->save();
    }
}
