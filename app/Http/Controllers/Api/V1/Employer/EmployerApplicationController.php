<?php

namespace App\Http\Controllers\Api\V1\Employer;

use App\Http\Controllers\Controller;
use App\Enums\ApplicationStatus;
use App\Http\Requests\ListEmployerApplicationsRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\EmployerApplicationResource;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class EmployerApplicationController extends Controller
{
    public function index(ListEmployerApplicationsRequest $request, Job $job): AnonymousResourceCollection
    {
        Gate::authorize('viewAnyForJob', [Application::class, $job]);

        $applications = $job->applications()
            ->with(['candidate.candidateProfile'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return EmployerApplicationResource::collection($applications);
    }

    public function updateStatus(UpdateApplicationStatusRequest $request, Application $application): JsonResponse
    {
        // job must be loaded before Gate::authorize — ApplicationPolicy::updateStatus needs the relationship
        $application->load('job');
        Gate::authorize('updateStatus', $application);

        $to = ApplicationStatus::from($request->validated('status'));

        if (! $application->status->toState()->canTransitionTo($to, 'employer')) {
            return response()->json(['message' => 'This transition is not permitted.'], 422);
        }

        $application->update(['status' => $to]);

        return response()->json([
            'data'    => new EmployerApplicationResource($application->load('candidate.candidateProfile')),
            'message' => "Application marked as {$to->value}.",
        ]);
    }

    public function show(Application $application): JsonResponse
    {
        // job must be loaded before Gate::authorize — ApplicationPolicy::viewEmployer needs the relationship
        $application->load('job');
        Gate::authorize('viewEmployer', $application);

        Application::where('id', $application->id)
            ->whereNull('viewed_at')
            ->update(['viewed_at' => now()]);

        $application->viewed_at = now();
        $application->load('candidate.candidateProfile');

        return response()->json(['data' => new EmployerApplicationResource($application)]);
    }
}
