<?php

namespace Database\Factories;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Job>
 */
class JobFactory extends Factory
{
    protected $model = Job::class;

    public function definition(): array
    {
        return [
            'employer_id' => User::factory()->employer(),
            'title' => fake()->jobTitle(),
            'status' => JobStatus::Active,
            'views_count' => fake()->numberBetween(0, 100),
            'application_deadline' => now()->addYear()->toDateString(),
        ];
    }

    public function forEmployer(User $employer): static
    {
        return $this->state(fn () => ['employer_id' => $employer->id]);
    }

    public function closed(): static
    {
        return $this->state(fn () => ['status' => JobStatus::Closed]);
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => JobStatus::Draft]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => JobStatus::Pending]);
    }

    public function withExpiredDeadline(): static
    {
        return $this->state(fn () => ['application_deadline' => now()->subDay()->toDateString()]);
    }
}
