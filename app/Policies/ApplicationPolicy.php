<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    public function create(User $user): bool
    {
        return $user->role === 'candidate' && ! $user->banned;
    }

    public function view(User $user, Application $application): bool
    {
        return $user->id === $application->candidate_id;
    }

    public function withdraw(User $user, Application $application): bool
    {
        return $user->id === $application->candidate_id;
    }
}
