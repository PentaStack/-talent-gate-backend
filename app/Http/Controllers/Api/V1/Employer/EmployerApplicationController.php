<?php

namespace App\Http\Controllers\Api\V1\Employer;

use App\Http\Controllers\Controller;
use App\Enums\ApplicationStatus;
use App\Http\Requests\ListEmployerApplicationsRequest;
use App\Http\Requests\UpdateApplicationNotesRequest;
use App\Http\Requests\UpdateApplicationStatusRequest;
use App\Http\Resources\EmployerApplicationResource;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;

class EmployerApplicationController extends Controller
{
    public function jobs(): JsonResponse
    {
        $jobs = Job::where('employer_id', auth()->id())
            ->withCount('applications')
            ->latest()
            ->get()
            ->map(fn (Job $job) => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'status'               => $job->status->value,
                'application_deadline' => $job->application_deadline?->toDateString(),
                'applications_count'   => $job->applications_count,
            ]);

        return response()->json(['data' => $jobs]);
    }

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

        if ($to === ApplicationStatus::Accepted) {
            app(\App\Services\PaymentService::class)->createForAcceptedApplication($application);
        }

        return response()->json([
            'data'    => new EmployerApplicationResource($application->load(['candidate.candidateProfile', 'payment'])),
            'message' => "Application marked as {$to->value}.",
        ]);
    }

    public function streamResume(Application $application): Response|JsonResponse
    {
        $application->load('job');
        Gate::authorize('viewEmployer', $application);

        $application->load('candidate.candidateProfile');
        $url = $application->candidate->candidateProfile?->resume_full_url;

        if (!$url) {
            return response()->json(['message' => 'No resume on file.'], 404);
        }

        $remote = Http::get($url);

        if ($remote->failed()) {
            return response()->json(['message' => 'Resume could not be retrieved.'], 502);
        }

        $body = $remote->body();
        $isPdf = str_starts_with($body, '%PDF');

        return response($body, 200, [
            'Content-Type'        => $isPdf ? 'application/pdf' : 'application/octet-stream',
            'Content-Disposition' => 'inline; filename="resume' . ($isPdf ? '.pdf' : '') . '"',
        ]);
    }

    public function updateNotes(UpdateApplicationNotesRequest $request, Application $application): JsonResponse
    {
        $application->load('job');
        Gate::authorize('updateNotes', $application);

        $application->update(['notes' => $request->validated('notes')]);

        return response()->json([
            'data'    => new EmployerApplicationResource($application->load('candidate.candidateProfile')),
            'message' => 'Notes saved.',
        ]);
    }

    public function show(Application $application): JsonResponse
    {
        // job must be loaded before Gate::authorize — ApplicationPolicy::viewEmployer needs the relationship
        $application->load('job');
        Gate::authorize('viewEmployer', $application);

        if ($application->viewed_at === null) {
            $application->viewed_at = now();
            $application->save();
        }

        $application->load(['candidate.candidateProfile', 'payment']);

        return response()->json(['data' => new EmployerApplicationResource($application)]);
    }
}
