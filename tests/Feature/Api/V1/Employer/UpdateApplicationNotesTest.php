<?php

namespace Tests\Feature\Api\V1\Employer;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateApplicationNotesTest extends TestCase
{
    use RefreshDatabase;

    private function url(Application $application): string
    {
        return "/api/v1/employer/applications/{$application->id}/notes";
    }

    private function makeApplication(User $employer): Application
    {
        return Application::factory()
            ->for(Job::factory()->forEmployer($employer), 'job')
            ->create();
    }

    // ── auth & role ──────────────────────────────────────────────────────────

    public function test_unauthenticated_gets_401(): void
    {
        $application = Application::factory()->create();

        $this->patchJson($this->url($application), ['notes' => 'Good.'])
            ->assertStatus(401);
    }

    public function test_candidate_role_gets_403(): void
    {
        $candidate   = User::factory()->candidate()->create();
        $application = Application::factory()->create();

        $this->actingAs($candidate)
            ->patchJson($this->url($application), ['notes' => 'Good.'])
            ->assertStatus(403);
    }

    // ── IDOR guard ────────────────────────────────────────────────────────────

    public function test_non_owner_employer_gets_403(): void
    {
        $owner = User::factory()->employer()->create();
        $other = User::factory()->employer()->create();
        $application = $this->makeApplication($owner);

        $this->actingAs($other)
            ->patchJson($this->url($application), ['notes' => 'Sneaky.'])
            ->assertStatus(403);
    }

    // ── validation ───────────────────────────────────────────────────────────

    public function test_notes_over_5000_chars_returns_422(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['notes' => str_repeat('x', 5001)])
            ->assertStatus(422);
    }

    // ── happy path ────────────────────────────────────────────────────────────

    public function test_owner_can_save_notes(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['notes' => 'Strong candidate.'])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', 'Strong candidate.');

        $this->assertDatabaseHas('applications', [
            'id'    => $application->id,
            'notes' => 'Strong candidate.',
        ]);
    }

    public function test_owner_can_clear_notes(): void
    {
        $employer    = User::factory()->employer()->create();
        $application = $this->makeApplication($employer);
        $application->update(['notes' => 'Old note.']);

        $this->actingAs($employer)
            ->patchJson($this->url($application), ['notes' => null])
            ->assertStatus(200)
            ->assertJsonPath('data.notes', null);

        $this->assertDatabaseHas('applications', [
            'id'    => $application->id,
            'notes' => null,
        ]);
    }
}
