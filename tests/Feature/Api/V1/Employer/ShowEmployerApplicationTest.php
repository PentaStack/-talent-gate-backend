<?php

namespace Tests\Feature\Api\V1\Employer;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ShowEmployerApplicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function url(Application $application): string
    {
        return "/api/v1/employer/applications/{$application->id}";
    }

    // ── auth & role gates ─────────────────────────────────────────────────────

    public function test_unauthenticated_gets_401(): void
    {
        $application = Application::factory()->create();

        $this->getJson($this->url($application))->assertStatus(401);
    }

    public function test_candidate_role_gets_403(): void
    {
        $candidate   = User::factory()->candidate()->create();
        $application = Application::factory()->create();

        $this->actingAs($candidate)
            ->getJson($this->url($application))
            ->assertStatus(403);
    }

    // ── authorization: application ownership (IDOR guard) ────────────────────

    public function test_employer_gets_403_for_another_employers_application(): void
    {
        $owner       = User::factory()->employer()->create();
        $other       = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($owner)->create();
        $application = Application::factory()->for($job)->create();

        $this->actingAs($other)
            ->getJson($this->url($application))
            ->assertStatus(403);
    }

    // ── happy path ────────────────────────────────────────────────────────────

    public function test_authenticated_employer_gets_200_for_own_application(): void
    {
        $employer    = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($employer)->create();
        $application = Application::factory()->for($job)->create();

        $this->actingAs($employer)
            ->getJson($this->url($application))
            ->assertStatus(200);
    }

    public function test_detail_response_has_correct_shape(): void
    {
        $employer    = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($employer)->create();
        $candidate   = User::factory()->candidate()->create();
        CandidateProfile::factory()->for($candidate, 'user')->create();
        $application = Application::factory()->for($job)->for($candidate, 'candidate')->create();

        $response = $this->actingAs($employer)->getJson($this->url($application));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id', 'status', 'submitted_at', 'viewed_at', 'cover_letter',
                    'candidate' => ['id', 'name', 'experience_level', 'skills', 'avatar_url'],
                ],
            ]);
    }

    // ── viewed_at behaviour ───────────────────────────────────────────────────

    public function test_viewed_at_is_set_on_first_access(): void
    {
        $employer    = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($employer)->create();
        $application = Application::factory()->for($job)->create(['viewed_at' => null]);

        $this->actingAs($employer)->getJson($this->url($application));

        $this->assertNotNull($application->fresh()->viewed_at);
    }

    public function test_viewed_at_is_not_overwritten_on_second_access(): void
    {
        $employer    = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($employer)->create();
        $firstView   = now()->subMinutes(5)->startOfSecond();
        $application = Application::factory()->for($job)->create(['viewed_at' => $firstView]);

        $this->actingAs($employer)->getJson($this->url($application));

        $this->assertEquals($firstView, $application->fresh()->viewed_at);
    }
}
