<?php

namespace App\Http\Controllers\Api\V1\Employer;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListEmployerApplicationsRequest;
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

    public function show(Application $application): JsonResponse
    {
        $application->load('job');
        Gate::authorize('viewEmployer', $application);

        Application::where('id', $application->id)
            ->whereNull('viewed_at')
            ->update(['viewed_at' => now()]);

        $application->refresh()->load('candidate.candidateProfile');

        return response()->json(['data' => new EmployerApplicationResource($application)]);
    }
}
