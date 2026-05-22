<?php

namespace Database\Factories;

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
            'status' => 'approved',
            'views_count' => fake()->numberBetween(0, 100),
        ];
    }

    public function forEmployer(User $employer): static
    {
        return $this->state(fn () => ['employer_id' => $employer->id]);
    }
}
