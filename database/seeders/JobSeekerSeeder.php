<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class JobSeekerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Job Seeker',
            'email' => 'jobseeker@hirely.test',
            'password' => Hash::make('password'),
            'role' => 'job_seeker',
            'email_verified_at' => now(),
        ]);
    }
}
