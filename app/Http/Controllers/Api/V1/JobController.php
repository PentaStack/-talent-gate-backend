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
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = Job::where('status', JobStatus::Active)
            ->where('application_deadline', '>=', now()->toDateString())
            ->with(['employer.employerProfile', 'category', 'technologies'])
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $jobs->map(fn (Job $job) => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'description'          => $job->description,
                'salary_range'         => $job->salary_range,
                'work_type'            => $job->work_type?->value,
                'location'             => $job->location,
                'application_deadline' => $job->application_deadline?->toDateString(),
                'category'             => $job->category ? ['id' => $job->category->id, 'name' => $job->category->name] : null,
                'technologies'         => $job->technologies->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
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

    public function show(Job $job): JsonResponse
    {
        abort_if($job->status !== JobStatus::Active, 404);

        $job->increment('views_count');
        $job->load(['employer.employerProfile', 'category', 'technologies']);

        return response()->json([
            'data' => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'description'          => $job->description,
                'requirements'         => $job->requirements,
                'salary_range'         => $job->salary_range,
                'work_type'            => $job->work_type?->value,
                'location'             => $job->location,
                'application_deadline' => $job->application_deadline?->toDateString(),
                'category'             => $job->category ? ['id' => $job->category->id, 'name' => $job->category->name] : null,
                'technologies'         => $job->technologies->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
                'employer' => [
                    'company_name' => $job->employer->employerProfile?->company_name ?? $job->employer->name,
                    'logo_url'     => $job->employer->employerProfile?->logo_full_url,
                    'bio'          => $job->employer->employerProfile?->bio,
                    'website'      => $job->employer->employerProfile?->website,
                ],
                'views_count' => $job->views_count,
                'created_at'  => $job->created_at?->toDateString(),
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
