<?php

namespace Database\Factories;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Application>
 */
class ApplicationFactory extends Factory
{
    protected $model = Application::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'candidate_id' => User::factory()->candidate(),
            'status' => ApplicationStatus::Pending,
            'cover_letter' => fake()->paragraph(),
        ];
    }

    public function withdrawn(): static
    {
        return $this->state(fn () => ['status' => ApplicationStatus::Withdrawn]);
    }
}
