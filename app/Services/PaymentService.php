<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

class PaymentService
{
    public function createForAcceptedApplication(Application $application): Payment
    {
        $application->loadMissing('job');

        return Payment::create([
            'job_id' => $application->job_id,
            'employer_id' => $application->job->employer_id,
            'application_id' => $application->id,
            'amount' => config('payments.acceptance_fee', 50.00),
            'currency' => 'USD',
            'status' => 'pending',
            'provider' => 'stripe',
            'provider_reference' => 'pi_'.Str::lower(Str::random(24)),
        ]);
    }

    public function startPayPalCheckout(User $employer, int $jobId, float $amount): array
    {
        $payment = Payment::create([
            'job_id' => $jobId,
            'employer_id' => $employer->id,
            'amount' => $amount,
            'currency' => 'USD',
            'status' => 'pending',
            'provider' => 'paypal',
            'provider_reference' => 'PAYPAL-'.Str::upper(Str::random(12)),
        ]);

        return [
            'payment_id' => $payment->id,
            'approval_url' => config('payments.paypal.approval_url').'?payment='.$payment->id,
        ];
    }

    public function markPaidByProviderReference(string $providerReference): ?Payment
    {
        $payment = Payment::query()
            ->where('provider_reference', $providerReference)
            ->first();

        if ($payment === null) {
            return null;
        }

        $payment->update(['status' => 'paid']);

        return $payment;
    }
}
