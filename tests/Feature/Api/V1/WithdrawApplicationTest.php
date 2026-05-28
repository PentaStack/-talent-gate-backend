<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\User;
use App\Notifications\ApplicationStatusChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WithdrawApplicationTest extends TestCase
{
    use RefreshDatabase;

    private function url(Application $application): string
    {
        return "/api/v1/applications/{$application->id}/withdraw";
    }

    // ── C1: tracer bullet ────────────────────────────────────────────────────

    public function test_candidate_can_withdraw_pending_application(): void
    {
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Pending,
        ]);

        $response = $this->actingAs($candidate)->patchJson($this->url($application));

        $response->assertStatus(200)
            ->assertJsonPath('data.status', ApplicationStatus::Withdrawn->value)
            ->assertJsonPath('message', 'Application withdrawn.');

        $this->assertDatabaseHas('applications', [
            'id'     => $application->id,
            'status' => ApplicationStatus::Withdrawn->value,
        ]);
    }

    // ── C2: shortlisted → withdrawn ──────────────────────────────────────────

    public function test_candidate_can_withdraw_shortlisted_application(): void
    {
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Shortlisted,
        ]);

        $this->actingAs($candidate)
            ->patchJson($this->url($application))
            ->assertStatus(200)
            ->assertJsonPath('data.status', ApplicationStatus::Withdrawn->value);
    }

    // ── C3–C5: terminal states return 422 ───────────────────────────────────

    public function test_withdraw_from_accepted_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Accepted,
        ]);

        $this->actingAs($candidate)
            ->patchJson($this->url($application))
            ->assertStatus(422);
    }

    public function test_withdraw_from_rejected_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Rejected,
        ]);

        $this->actingAs($candidate)
            ->patchJson($this->url($application))
            ->assertStatus(422);
    }

    public function test_withdraw_from_withdrawn_returns_422(): void
    {
        $candidate = User::factory()->candidate()->create();
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Withdrawn,
        ]);

        $this->actingAs($candidate)
            ->patchJson($this->url($application))
            ->assertStatus(422);
    }

    // ── C6: ownership ────────────────────────────────────────────────────────

    public function test_another_candidates_application_returns_403(): void
    {
        $candidate = User::factory()->candidate()->create();
        $other     = User::factory()->candidate()->create();
        $application = Application::factory()->for($other, 'candidate')->create([
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($candidate)
            ->patchJson($this->url($application))
            ->assertStatus(403);
    }

    // ── C7: notification ─────────────────────────────────────────────────────

    public function test_candidate_receives_notification_on_withdrawal(): void
    {
        Notification::fake();

        $candidate = User::factory()->candidate()->create();
        $application = Application::factory()->for($candidate, 'candidate')->create([
            'status' => ApplicationStatus::Pending,
        ]);

        $this->actingAs($candidate)->patchJson($this->url($application));

        Notification::assertSentTo($candidate, ApplicationStatusChangedNotification::class);
    }

    // ── C8–C9: auth / role gates ─────────────────────────────────────────────

    public function test_unauthenticated_gets_401(): void
    {
        $application = Application::factory()->create();

        $this->patchJson($this->url($application))->assertStatus(401);
    }

    public function test_employer_role_gets_403(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = Application::factory()->create(['status' => ApplicationStatus::Pending]);

        $this->actingAs($employer)
            ->patchJson($this->url($application))
            ->assertStatus(403);
    }
}
