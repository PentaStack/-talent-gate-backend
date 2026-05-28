<?php

namespace App\States\Application;

use App\Enums\ApplicationStatus;

abstract class TerminalState extends ApplicationState
{
    final public function canTransitionTo(ApplicationStatus $to, string $role): bool
    {
        return false;
    }
}
