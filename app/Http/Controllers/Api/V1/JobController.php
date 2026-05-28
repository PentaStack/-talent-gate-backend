<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreApplicationRequest;
use App\Http\Resources\ApplicationResource;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = Job::where('status', JobStatus::Active)
            ->where('application_deadline', '>=', now()->toDateString())
            ->with('employer.employerProfile')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $jobs->map(fn (Job $job) => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'application_deadline' => $job->application_deadline?->toDateString(),
                'employer' => [
                    'company_name' => $job->employer->employerProfile?->company_name ?? $job->employer->name,
                    'logo_url'     => $job->employer->employerProfile?->logo_full_url,
                ],
            ]),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page'    => $jobs->lastPage(),
                'per_page'     => $jobs->perPage(),
                'total'        => $jobs->total(),
            ],
        ]);
    }

    public function apply(StoreApplicationRequest $request, Job $job): JsonResponse
    {
        Gate::authorize('create', Application::class);

        try {
            $application = Application::create([
                'job_id'       => $job->id,
                'candidate_id' => $request->user()->id,
                'cover_letter' => $request->validated('cover_letter'),
                'status'       => ApplicationStatus::Pending,
            ]);
        } catch (UniqueConstraintViolationException) {
            return response()->json(
                ['message' => 'You have already applied for this job.'],
                422
            );
        }

        return (new ApplicationResource($application))
            ->response()
            ->setStatusCode(201);
    }
}
