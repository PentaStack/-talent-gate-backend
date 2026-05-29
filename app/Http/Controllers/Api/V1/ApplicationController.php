<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ApplicationController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $applications = Application::where('candidate_id', auth()->id())
            ->with(['job.employer.employerProfile'])
            ->latest()
            ->paginate(15);

        return ApplicationResource::collection($applications);
    }

    public function show(Application $application): JsonResponse
    {
        Gate::authorize('view', $application);

        $application->load(['job.employer.employerProfile']);

        $data = (new ApplicationResource($application))->toArray(request());
        $data['cover_letter'] = $application->cover_letter;

        return response()->json(['data' => $data]);
    }

    public function withdraw(Application $application): JsonResponse
    {
        Gate::authorize('withdraw', $application);

        if (! $application->status->toState()->canTransitionTo(ApplicationStatus::Withdrawn, 'candidate')) {
            return response()->json(['message' => 'This application cannot be withdrawn.'], 422);
        }

        $application->update(['status' => ApplicationStatus::Withdrawn]);
        $application->load(['job.employer.employerProfile']);

        return response()->json([
            'data'    => new ApplicationResource($application),
            'message' => 'Application withdrawn.',
        ]);
    }
}
