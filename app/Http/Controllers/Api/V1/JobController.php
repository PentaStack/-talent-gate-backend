<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ApplicationStatus;
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
