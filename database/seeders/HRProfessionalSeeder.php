<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class HRProfessionalSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::query()->firstOrCreate(
            ['slug' => 'hirely-labs'],
            [
                'name' => 'Hirely Labs',
                'description' => 'Sample hiring organization for Hirely.',
                'industry' => 'Software',
                'size' => '11-50',
                'location' => 'Colombo',
                'is_verified' => true,
            ],
        );

        User::create([
            'name' => 'HR Professional',
            'email' => 'hr@hirely.test',
            'password' => Hash::make('password'),
            'role' => 'hr_professional',
            'company_id' => $company->id,
            'email_verified_at' => now(),
        ]);
    }
}
