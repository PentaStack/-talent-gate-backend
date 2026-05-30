<?php

namespace App\Http\Controllers\Api\V1\Employer;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreJobRequest;
use App\Http\Requests\UpdateJobRequest;
use App\Http\Resources\JobResource;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class EmployerJobController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $jobs = Job::where('employer_id', auth()->id())
            ->with(['category', 'technologies'])
            ->withCount('applications')
            ->latest()
            ->paginate(20);

        return JobResource::collection($jobs);
    }

    public function store(StoreJobRequest $request): JsonResponse
    {
        $data = $request->validated();
        $techIds = $data['technology_ids'] ?? [];
        unset($data['technology_ids']);

        $data['employer_id'] = auth()->id();
        $data['status']      = $data['status'] ?? JobStatus::Pending->value;

        $job = Job::create($data);
        $job->technologies()->sync($techIds);

        return (new JobResource($job->load(['category', 'technologies'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Job $job): JobResource
    {
        abort_if($job->employer_id !== auth()->id(), 403);

        return new JobResource($job->load(['category', 'technologies'])->loadCount('applications'));
    }

    public function update(UpdateJobRequest $request, Job $job): JobResource
    {
        abort_if($job->employer_id !== auth()->id(), 403);

        $data = $request->validated();
        $techIds = $data['technology_ids'] ?? null;
        unset($data['technology_ids']);

        $job->update($data);

        if ($techIds !== null) {
            $job->technologies()->sync($techIds);
        }

        return new JobResource($job->load(['category', 'technologies'])->loadCount('applications'));
    }

    public function destroy(Job $job): JsonResponse
    {
        abort_if($job->employer_id !== auth()->id(), 403);

        $job->delete();

        return response()->json(['message' => 'Job deleted.']);
    }
}
