<?php

namespace App\Enums;

use App\States\Application\AcceptedState;
use App\States\Application\ApplicationState;
use App\States\Application\PendingState;
use App\States\Application\RejectedState;
use App\States\Application\ShortlistedState;
use App\States\Application\WithdrawnState;

enum ApplicationStatus: string
{
    case Pending = 'pending';
    case Shortlisted = 'shortlisted';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Withdrawn = 'withdrawn';

    public function toState(): ApplicationState
    {
        return match($this) {
            self::Pending     => new PendingState,
            self::Shortlisted => new ShortlistedState,
            self::Accepted    => new AcceptedState,
            self::Rejected    => new RejectedState,
            self::Withdrawn   => new WithdrawnState,
        };
    }
}
