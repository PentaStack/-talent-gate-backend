<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use App\Services\StripeWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        StripeWebhookVerifier $verifier,
        PaymentService $payments,
    ): JsonResponse {
        $verifier->assertValid($request);

        $payload = $request->json()->all();

        if (($payload['type'] ?? null) === 'payment_intent.succeeded') {
            $reference = $payload['data']['object']['id'] ?? null;

            if (is_string($reference)) {
                $payments->markPaidByProviderReference($reference);
            }
        }

        return response()->json(['received' => true]);
    }
}
