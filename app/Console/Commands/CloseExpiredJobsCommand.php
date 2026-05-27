<?php

namespace App\Console\Commands;

use App\Enums\JobStatus;
use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class CloseExpiredJobsCommand extends Command
{
    protected $signature = 'app:close-expired-jobs';
    protected $description = 'Close all active job listings whose application deadline has passed.';

    public function handle(): int
    {
        $count = Job::query()
            ->where('status', JobStatus::Active)
            ->where('application_deadline', '<', Carbon::today('UTC')->toDateString())
            ->update(['status' => JobStatus::Closed]);

        $this->info("Closed {$count} expired job listing(s).");

        return self::SUCCESS;
    }
}
