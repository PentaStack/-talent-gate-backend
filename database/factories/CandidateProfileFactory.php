<?php

namespace Database\Factories;

use App\Models\CandidateProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CandidateProfile>
 */
class CandidateProfileFactory extends Factory
{
    protected $model = CandidateProfile::class;

    public function definition(): array
    {
        return [
            'user_id'          => User::factory()->candidate(),
            'bio'              => fake()->sentence(),
            'skills'           => ['PHP', 'Laravel'],
            'resume_url'       => null,
            'avatar_url'       => null,
            'experience_level' => fake()->randomElement(['junior', 'mid', 'senior']),
        ];
    }
}
