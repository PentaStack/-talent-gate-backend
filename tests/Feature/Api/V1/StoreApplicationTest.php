<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\NewApplicationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class StoreApplicationTest extends TestCase
{
    use RefreshDatabase;

    private const COVER_LETTER = 'I am very interested in this position and believe my skills are a great match.';

    private function url(Job $job): string
    {
        return "/api/v1/jobs/{$job->id}/apply";
    }

    // ── Happy path ────────────────────────────────────────────────────────────

    public function test_candidate_can_apply_to_active_job_and_receives_201(): void
    {
        Notification::fake();

        $employer = User::factory()->employer()->create();
        $job = Job::factory()->forEmployer($employer)->create();
        $candidate = User::factory()->candidate()->create();

        $response = $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER]);

        $response->assertStatus(201)
            ->assertJsonStructure(['data' => ['id', 'status', 'submitted_at']])
            ->assertJsonPath('data.status', ApplicationStatus::Pending->value);

        $this->assertDatabaseHas('applications', [
            'job_id'       => $job->id,
            'candidate_id' => $candidate->id,
            'cover_letter' => self::COVER_LETTER,
        ]);

        $application = Application::first();
        $this->assertNotNull($application->submitted_at);

        Notification::assertSentTo($employer, NewApplicationNotification::class);
    }

    public function test_submitted_at_is_not_overridable_via_request_body(): void
    {
        Notification::fake();

        $job = Job::factory()->create();
        $candidate = User::factory()->candidate()->create();

        $fakeDate = '2000-01-01T00:00:00+00:00';

        $this->actingAs($candidate)
            ->postJson($this->url($job), [
                'cover_letter' => self::COVER_LETTER,
                'submitted_at' => $fakeDate,
            ])
            ->assertStatus(201);

        $application = Application::first();
        $this->assertNotEquals($fakeDate, $application->submitted_at?->toIso8601String());
    }

    public function test_candidate_id_from_request_body_is_ignored(): void
    {
        Notification::fake();

        $job = Job::factory()->create();
        $candidate = User::factory()->candidate()->create();
        $otherUserId = User::factory()->candidate()->create()->id;

        $this->actingAs($candidate)
            ->postJson($this->url($job), [
                'cover_letter' => self::COVER_LETTER,
                'candidate_id' => $otherUserId,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('applications', [
            'job_id'       => $job->id,
            'candidate_id' => $candidate->id,
        ]);
        $this->assertDatabaseMissing('applications', ['candidate_id' => $otherUserId]);
    }

    // ── Auth / role gates ─────────────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $job = Job::factory()->create();

        $this->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(401);
    }

    public function test_employer_cannot_apply_returns_403(): void
    {
        $employer = User::factory()->employer()->create();
        $job = Job::factory()->forEmployer($employer)->create();

        $this->actingAs($employer)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(403);
    }

    public function test_admin_cannot_apply_returns_403(): void
    {
        $admin = User::factory()->admin()->create();
        $job = Job::factory()->create();

        $this->actingAs($admin)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(403);
    }

    public function test_banned_candidate_receives_403(): void
    {
        $candidate = User::factory()->candidate()->create(['banned' => true]);
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(403);
    }

    // ── Job-state gates ───────────────────────────────────────────────────────

    public function test_apply_to_draft_job_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->draft()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422)
            ->assertJsonPath('errors.job.0', 'This job listing is not accepting applications.');
    }

    public function test_apply_to_pending_job_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->pending()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422);
    }

    public function test_apply_to_closed_job_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->closed()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422);
    }

    public function test_apply_to_job_with_rejected_status_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->state(['status' => JobStatus::Rejected])->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422);
    }

    public function test_apply_to_job_with_expired_deadline_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->withExpiredDeadline()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422)
            ->assertJsonPath('errors.job.0', 'The application deadline for this job has passed.');
    }

    // ── Duplicate gates ───────────────────────────────────────────────────────

    public function test_duplicate_application_returns_422_with_already_applied_message(): void
    {
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->create();

        Application::factory()->create([
            'job_id'       => $job->id,
            'candidate_id' => $candidate->id,
            'status'       => ApplicationStatus::Pending,
        ]);

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422)
            ->assertJsonPath('errors.application.0', 'You have already applied for this job.');
    }

    public function test_applying_after_withdrawal_returns_422_with_withdrew_message(): void
    {
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->create();

        Application::factory()->withdrawn()->create([
            'job_id'       => $job->id,
            'candidate_id' => $candidate->id,
        ]);

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422)
            ->assertJsonPath(
                'errors.application.0',
                'You previously withdrew your application for this job and cannot re-apply.'
            );
    }

    public function test_concurrent_duplicate_caught_by_unique_constraint_returns_422(): void
    {
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->create();

        Application::factory()->create([
            'job_id'       => $job->id,
            'candidate_id' => $candidate->id,
        ]);

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(422);
    }

    // ── Cover letter validation ───────────────────────────────────────────────

    public function test_missing_cover_letter_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cover_letter']);
    }

    public function test_empty_cover_letter_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => ''])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cover_letter']);
    }

    public function test_cover_letter_exceeding_5000_chars_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => str_repeat('a', 5001)])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cover_letter']);
    }

    public function test_cover_letter_at_5000_chars_is_accepted(): void
    {
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $job = Job::factory()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => str_repeat('a', 5000)])
            ->assertStatus(201);
    }

    // ── Notification ─────────────────────────────────────────────────────────

    public function test_employer_receives_new_application_notification(): void
    {
        Notification::fake();

        $employer = User::factory()->employer()->create();
        $job = Job::factory()->forEmployer($employer)->create();
        $candidate = User::factory()->candidate()->create();

        $this->actingAs($candidate)
            ->postJson($this->url($job), ['cover_letter' => self::COVER_LETTER])
            ->assertStatus(201);

        Notification::assertSentTo($employer, NewApplicationNotification::class);
        Notification::assertNotSentTo($candidate, NewApplicationNotification::class);
    }
}