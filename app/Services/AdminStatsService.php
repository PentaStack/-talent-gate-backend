<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;

class AdminStatsService
{
    /**
     * @return array{users: int, jobs: int, applications: int}
     */
    public function counts(): array
    {
        return [
            'users' => User::count(),
            'jobs' => Job::count(),
            'applications' => Application::count(),
        ];
    }
}
