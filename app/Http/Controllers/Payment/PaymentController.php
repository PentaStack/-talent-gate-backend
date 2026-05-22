<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Payment::query()
            ->where('employer_id', $request->user()->id)
            ->with(['job:id,title', 'application.candidate:id,name'])
            ->latest();

        if ($request->filled('application_id')) {
            $query->where('application_id', $request->integer('application_id'));
        }

        $payments = $query->get()->map(fn (Payment $payment) => [
            'id' => $payment->id,
            'application_id' => $payment->application_id,
            'job_id' => $payment->job_id,
            'job_title' => $payment->job?->title,
            'candidate_name' => $payment->application?->candidate?->name,
            'amount' => (float) $payment->amount,
            'currency' => $payment->currency,
            'status' => $payment->status,
            'provider' => $payment->provider,
            'provider_reference' => $payment->provider_reference,
            'created_at' => $payment->created_at?->toIso8601String(),
        ]);

        return response()->json(['data' => $payments]);
    }
}
