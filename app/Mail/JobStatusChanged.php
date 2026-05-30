<?php

namespace App\Mail;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class JobStatusChanged extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Job $job) {}

    public function build()
    {
        $subject = $this->job->status === JobStatus::Active
            ? "Your job listing has been approved – {$this->job->title}"
            : "Your job listing requires attention – {$this->job->title}";

        return $this->subject($subject)->view('emails.job-status-changed');
    }

    public function failed(Throwable $exception): void
    {
        Log::error('Failed to send job status changed email.', [
            'job_id' => $this->job->id,
            'error'  => $exception->getMessage(),
        ]);
    }
}
