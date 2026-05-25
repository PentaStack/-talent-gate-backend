<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewApplicationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $jobTitle,
        public string $candidateName,
        public int $applicationId,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'application_id' => $this->applicationId,
            'job_title' => $this->jobTitle,
            'candidate_name' => $this->candidateName,
            'message' => "{$this->candidateName} applied for {$this->jobTitle}.",
        ];
    }
}
