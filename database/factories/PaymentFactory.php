<?php

namespace Database\Factories;

use App\Models\Job;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'job_id' => Job::factory(),
            'employer_id' => User::factory()->employer(),
            'application_id' => null,
            'amount' => 50.00,
            'currency' => 'USD',
            'status' => 'pending',
            'provider' => 'stripe',
            'provider_reference' => fake()->uuid(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }
}
