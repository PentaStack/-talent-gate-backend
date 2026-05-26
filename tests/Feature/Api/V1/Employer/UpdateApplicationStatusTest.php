<?php

namespace Tests\Feature\Api\V1\Employer;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UpdateApplicationStatusTest extends TestCase
{
    use RefreshDatabase;

    private function url(Application $application): string
    {
        return "/api/v1/employer/applications/{$application->id}/status";
    }

    private function makeApplication(User $employer, ApplicationStatus $status): Application
    {
        return Application::factory()
            ->for(Job::factory()->forEmployer($employer), 'job')
            ->create(['status' => $status]);
    }

    // ── auth & role ──────────────────────────────────────────────────────────

    public function test_unauthenticated_gets_401(): void
    {
        $application = Application::factory()->create();

        $this->patchJson($this->url($application), ['status' => 'shortlisted'])
            ->assertStatus(401);
    }

    public function test_candidate_role_gets_403(): void
    {
        $candidate   = User::factory()->candidate()->create();
        $application = Application::factory()->create();

        $this->actingAs($candidate)
            ->patchJson($this->url($application), ['status' => 'shortlisted'])
            ->assertStatus(403);
    }

    // ── validation ───────────────────────────────────────────────────────────

    public function test_missing_status_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Pending);

        $this->actingAs($employer)
            ->patchJson($this->url($application), [])
            ->assertStatus(422);
    }

    public function test_status_withdrawn_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Pending);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'withdrawn'])
            ->assertStatus(422);
    }

    public function test_unrecognised_status_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Pending);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'foobar'])
            ->assertStatus(422);
    }

    // ── valid transitions ─────────────────────────────────────────────────────

    public function test_employer_can_shortlist_pending_application(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Pending);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'shortlisted'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'shortlisted')
            ->assertJsonPath('message', 'Application marked as shortlisted.');

        $this->assertDatabaseHas('applications', [
            'id'     => $application->id,
            'status' => ApplicationStatus::Shortlisted->value,
        ]);

        Notification::assertSentTo($application->candidate, ApplicationStatusChangedNotification::class);
    }

    public function test_employer_can_reject_pending_application(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Pending);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'rejected'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');
    }

    public function test_employer_can_accept_pending_application(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Pending);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'accepted'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('payments', ['application_id' => $application->id]);
    }

    public function test_employer_can_accept_shortlisted_application(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Shortlisted);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'accepted'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('payments', ['application_id' => $application->id]);
    }

    public function test_employer_can_reject_shortlisted_application(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Shortlisted);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'rejected'])
            ->assertStatus(200)
            ->assertJsonPath('data.status', 'rejected');
    }

    // ── state machine rejections ─────────────────────────────────────────────

    public function test_same_status_shortlisted_to_shortlisted_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Shortlisted);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'shortlisted'])
            ->assertStatus(422);
    }

    public function test_transition_from_accepted_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Accepted);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'rejected'])
            ->assertStatus(422);
    }

    public function test_transition_from_rejected_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Rejected);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'accepted'])
            ->assertStatus(422);
    }

    public function test_transition_from_withdrawn_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer, ApplicationStatus::Withdrawn);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['status' => 'accepted'])
            ->assertStatus(422);
    }

    // ── IDOR guard ────────────────────────────────────────────────────────────

    public function test_employer_gets_403_for_another_employers_application(): void
    {
        $owner       = User::factory()->employer()->create();
        $other       = User::factory()->employer()->create();
        $application = $this->makeApplication($owner, ApplicationStatus::Pending);

        $this->actingAs($other)
            ->patchJson($this->url($application), ['status' => 'shortlisted'])
            ->assertStatus(403);
    }
}
