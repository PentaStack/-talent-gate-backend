<?php

namespace App\Observers;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Payment;
use App\Notifications\ApplicationStatusChangedNotification;
use App\Notifications\ApplicationViewedNotification;
use App\Notifications\NewApplicationNotification;
use App\Services\PaymentService;

class ApplicationObserver
{
    public function __construct(private PaymentService $payments) {}

    public function created(Application $application): void
    {
        $application->loadMissing('job.employer', 'candidate');

        $application->job->employer->notify(new NewApplicationNotification(
            jobTitle: $application->job->title,
            candidateName: $application->candidate->name,
            applicationId: $application->id,
        ));
    }

    public function updated(Application $application): void
    {
        if ($application->wasChanged('status')) {
            $application->loadMissing('job', 'candidate');

            $application->candidate->notify(new ApplicationStatusChangedNotification(
                jobTitle: $application->job->title,
                status: $application->status->value,
                applicationId: $application->id,
            ));
        }

        if ($application->wasChanged('viewed_at') && $application->viewed_at !== null) {
            $application->loadMissing('job', 'candidate');

            $application->candidate->notify(new ApplicationViewedNotification(
                jobTitle:      $application->job->title,
                applicationId: $application->id,
            ));
        }

        if (! $application->wasChanged('status') || $application->status !== ApplicationStatus::Accepted) {
            return;
        }

        if (Payment::query()->where('application_id', $application->id)->exists()) {
            return;
        }

        $this->payments->createForAcceptedApplication($application);
    }
}
