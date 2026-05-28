<?php

namespace App\States\Application;

use App\Enums\ApplicationStatus;

class ShortlistedState extends ApplicationState
{
    public function canTransitionTo(ApplicationStatus $to, string $role): bool
    {
        return match($role) {
            'candidate' => $to === ApplicationStatus::Withdrawn,
            'employer'  => in_array($to, [
                ApplicationStatus::Accepted,
                ApplicationStatus::Rejected,
            ]),
            default => false,
        };
    }
}
