<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployerProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id'      => User::factory()->employer(),
            'company_name' => fake()->company(),
            'logo_url'     => null,
            'website'      => fake()->url(),
            'description'  => fake()->sentence(),
        ];
    }
}
