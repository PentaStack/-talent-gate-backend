<?php

namespace App\Http\Controllers\Admin;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Mail\JobStatusChanged;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class AdminJobController extends Controller
{
    public function index(): JsonResponse
    {
        $jobs = Job::where('status', JobStatus::Pending)
            ->with(['employer.employerProfile', 'category', 'technologies'])
            ->latest()
            ->paginate(30);

        return response()->json([
            'data' => $jobs->map(fn (Job $job) => $this->formatJob($job)),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page'    => $jobs->lastPage(),
                'total'        => $jobs->total(),
            ],
        ]);
    }

    public function approve(Job $job): JsonResponse
    {
        abort_if($job->status !== JobStatus::Pending, 422, 'Job is not pending.');

        $job->update(['status' => JobStatus::Active, 'rejection_reason' => null]);

        Mail::to($job->employer->email)->queue(new JobStatusChanged($job));

        return response()->json(['message' => 'Job approved.', 'data' => $this->formatJob($job->fresh())]);
    }

    public function reject(Request $request, Job $job): JsonResponse
    {
        abort_if($job->status !== JobStatus::Pending, 422, 'Job is not pending.');

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $job->update([
            'status'           => JobStatus::Rejected,
            'rejection_reason' => $validated['reason'] ?? null,
        ]);

        Mail::to($job->employer->email)->queue(new JobStatusChanged($job));

        return response()->json(['message' => 'Job rejected.', 'data' => $this->formatJob($job->fresh())]);
    }

    private function formatJob(Job $job): array
    {
        return [
            'id'                   => $job->id,
            'title'                => $job->title,
            'description'          => $job->description,
            'requirements'         => $job->requirements,
            'salary_range'         => $job->salary_range,
            'work_type'            => $job->work_type?->value,
            'location'             => $job->location,
            'status'               => $job->status->value,
            'rejection_reason'     => $job->rejection_reason,
            'application_deadline' => $job->application_deadline?->toDateString(),
            'created_at'           => $job->created_at?->toDateTimeString(),
            'category'             => $job->category ? ['id' => $job->category->id, 'name' => $job->category->name] : null,
            'technologies'         => $job->technologies->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values(),
            'employer'             => [
                'id'           => $job->employer->id,
                'name'         => $job->employer->name,
                'email'        => $job->employer->email,
                'company_name' => $job->employer->employerProfile?->company_name ?? $job->employer->name,
                'logo_url'     => $job->employer->employerProfile?->logo_full_url,
            ],
        ];
    }
}
