<?php

namespace App\Services;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class StripeWebhookVerifier
{
    public function assertValid(Request $request): void
    {
        $secret = config('services.stripe.webhook_secret');

        if (empty($secret)) {
            throw new AccessDeniedHttpException('Stripe webhook secret is not configured.');
        }

        $signature = $request->header('Stripe-Signature');

        if ($signature === null) {
            throw new AccessDeniedHttpException('Missing Stripe signature.');
        }

        $timestamp = null;
        $signatures = [];

        foreach (explode(',', $signature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, null);
            if ($key === 't') {
                $timestamp = $value;
            }
            if ($key === 'v1') {
                $signatures[] = $value;
            }
        }

        if ($timestamp === null || $signatures === []) {
            throw new AccessDeniedHttpException('Invalid Stripe signature header.');
        }

        $expected = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);
        $valid = collect($signatures)->contains(fn (string $sig) => hash_equals($expected, $sig));

        if (! $valid) {
            throw new AccessDeniedHttpException('Invalid Stripe signature.');
        }
    }
}
