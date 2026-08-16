<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HRProfessionalSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'HR Professional',
            'email' => 'hr@hirely.test',
            'password' => Hash::make('password'),
            'role' => 'hr_professional',
            'email_verified_at' => now(),
        ]);
    }
}
