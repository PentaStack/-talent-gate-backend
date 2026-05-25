<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;

class EmployerAnalyticsService
{
    /**
     * @return array{jobs: list<array{id: int, title: string, views: int, applications: int}>}
     */
    public function forEmployer(User $employer): array
    {
        $jobs = Job::query()
            ->where('employer_id', $employer->id)
            ->withCount('applications')
            ->orderBy('title')
            ->get();

        return [
            'jobs' => $jobs->map(fn (Job $job) => [
                'id' => $job->id,
                'title' => $job->title,
                'views' => $job->views_count,
                'applications' => $job->applications_count,
            ])->values()->all(),
        ];
    }
}
