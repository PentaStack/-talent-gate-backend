<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StripeCheckoutController extends Controller
{
    public function store(Request $request, PaymentService $paymentService): JsonResponse
    {
        $validated = $request->validate([
            'application_id' => ['required', 'exists:applications,id'],
        ]);

        $payment = Payment::where('application_id', $validated['application_id'])
            ->where('employer_id', $request->user()->id)
            ->where('provider', 'stripe')
            ->firstOrFail();

        $intent = $paymentService->createStripeIntent($payment);

        return response()->json([
            'client_secret' => $intent['client_secret'],
            'payment_id' => $payment->id,
        ]);
    }
}
