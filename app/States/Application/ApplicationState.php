<?php

namespace App\States\Application;

use App\Enums\ApplicationStatus;

abstract class ApplicationState
{
    abstract public function canTransitionTo(ApplicationStatus $to, string $role): bool;
}
