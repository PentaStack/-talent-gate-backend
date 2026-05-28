<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class Dev5Seeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Admin User', 'email' => 'admin@talentgate.test', 'role' => 'admin'],
            ['name' => 'Employer Co', 'email' => 'employer@talentgate.test', 'role' => 'employer'],
            ['name' => 'Jane Candidate', 'email' => 'candidate@talentgate.test', 'role' => 'candidate'],
        ];

        foreach ($users as $data) {
            User::query()->updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => $data['role'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
