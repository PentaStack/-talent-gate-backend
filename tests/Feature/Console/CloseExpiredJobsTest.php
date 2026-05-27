<?php

namespace Tests\Feature\Console;

use App\Enums\JobStatus;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CloseExpiredJobsTest extends TestCase
{
    use RefreshDatabase;

    // ── Slice 1: tracer bullet ────────────────────────────────────────────

    public function test_command_runs_successfully_on_empty_database(): void
    {
        $this->artisan('app:close-expired-jobs')->assertSuccessful();
    }

    // ── Slice 2: expired active jobs close ───────────────────────────────

    public function test_expired_active_jobs_are_closed(): void
    {
        $job = Job::factory()->withExpiredDeadline()->create(['status' => JobStatus::Active]);

        $this->artisan('app:close-expired-jobs')->assertSuccessful();

        $this->assertSame(JobStatus::Closed, $job->fresh()->status);
    }

    // ── Slice 3: today boundary ──────────────────────────────────────────

    public function test_job_with_todays_deadline_is_not_closed(): void
    {
        $job = Job::factory()->create([
            'status'               => JobStatus::Active,
            'application_deadline' => Carbon::today('UTC')->toDateString(),
        ]);

        $this->artisan('app:close-expired-jobs')->assertSuccessful();

        $this->assertSame(JobStatus::Active, $job->fresh()->status);
    }

    // ── Slice 4: future deadline unaffected ──────────────────────────────

    public function test_active_job_with_future_deadline_is_not_closed(): void
    {
        $job = Job::factory()->create(); // default deadline = +1 year

        $this->artisan('app:close-expired-jobs')->assertSuccessful();

        $this->assertSame(JobStatus::Active, $job->fresh()->status);
    }

    // ── Slice 5: non-active statuses unaffected ───────────────────────────

    public static function nonActiveStatuses(): array
    {
        return [
            'draft'    => [JobStatus::Draft],
            'pending'  => [JobStatus::Pending],
            'rejected' => [JobStatus::Rejected],
            'closed'   => [JobStatus::Closed],
        ];
    }

    #[DataProvider('nonActiveStatuses')]
    public function test_non_active_jobs_with_past_deadline_are_not_affected(JobStatus $status): void
    {
        $job = Job::factory()->withExpiredDeadline()->create(['status' => $status]);

        $this->artisan('app:close-expired-jobs')->assertSuccessful();

        $this->assertSame($status, $job->fresh()->status);
    }

    // ── Slice 6: integration with apply endpoint ─────────────────────────

    public function test_newly_closed_job_rejects_new_application(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->withExpiredDeadline()->create(['status' => JobStatus::Active]);

        $this->artisan('app:close-expired-jobs')->assertSuccessful();

        $this->actingAs($candidate)
            ->postJson("/api/v1/jobs/{$job->id}/apply", ['cover_letter' => 'Hello.'])
            ->assertStatus(422);
    }
}
