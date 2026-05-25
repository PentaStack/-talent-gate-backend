<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PayPalController extends Controller
{
    public function store(Request $request, PaymentService $payments): JsonResponse
    {
        $validated = $request->validate([
            'job_id' => ['required', 'integer', 'exists:job_listings,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $job = Job::query()->findOrFail($validated['job_id']);

        abort_unless($job->employer_id === $request->user()->id, 403);

        $checkout = $payments->startPayPalCheckout(
            $request->user(),
            $job->id,
            (float) $validated['amount'],
        );

        return response()->json($checkout, 201);
    }
}
