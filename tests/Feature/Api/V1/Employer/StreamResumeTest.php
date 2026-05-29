<?php

namespace Tests\Feature\Api\V1\Employer;

use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class StreamResumeTest extends TestCase
{
    use RefreshDatabase;

    private function url(Application $application): string
    {
        return "/api/v1/employer/applications/{$application->id}/resume";
    }

    private function makeApplicationWithResume(User $employer, ?string $resumeUrl): Application
    {
        $job       = Job::factory()->forEmployer($employer)->create();
        $candidate = User::factory()->candidate()->create();
        CandidateProfile::factory()->for($candidate, 'user')->create(['resume_url' => $resumeUrl]);

        return Application::factory()->for($job)->for($candidate, 'candidate')->create();
    }

    // ── auth & role ──────────────────────────────────────────────────────────

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

    public function test_non_owner_employer_gets_403(): void
    {
        $owner       = User::factory()->employer()->create();
        $other       = User::factory()->employer()->create();
        $application = $this->makeApplicationWithResume($owner, 'https://res.cloudinary.com/x/raw/upload/v1/r-1');

        $this->actingAs($other)
            ->getJson($this->url($application))
            ->assertStatus(403);
    }

    // ── no resume ─────────────────────────────────────────────────────────────

    public function test_owner_gets_404_when_no_resume(): void
    {
        $owner       = User::factory()->employer()->create();
        $application = $this->makeApplicationWithResume($owner, null);

        $this->actingAs($owner)
            ->getJson($this->url($application))
            ->assertStatus(404);
    }

    // ── content-type sniffing ──────────────────────────────────────────────────

    public function test_pdf_resume_is_served_inline_as_pdf(): void
    {
        Http::fake([
            '*' => Http::response("%PDF-1.4\n%fake pdf bytes", 200),
        ]);

        $owner       = User::factory()->employer()->create();
        $application = $this->makeApplicationWithResume($owner, 'https://res.cloudinary.com/x/raw/upload/v1/r-1');

        $response = $this->actingAs($owner)->get($this->url($application));

        $response->assertStatus(200);
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_non_pdf_resume_is_served_as_octet_stream(): void
    {
        Http::fake([
            '*' => Http::response("PK\x03\x04 fake docx bytes", 200),
        ]);

        $owner       = User::factory()->employer()->create();
        $application = $this->makeApplicationWithResume($owner, 'https://res.cloudinary.com/x/raw/upload/v1/r-1');

        $response = $this->actingAs($owner)->get($this->url($application));

        $response->assertStatus(200);
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
    }

    public function test_upstream_failure_returns_502(): void
    {
        Http::fake([
            '*' => Http::response('not found', 404),
        ]);

        $owner       = User::factory()->employer()->create();
        $application = $this->makeApplicationWithResume($owner, 'https://res.cloudinary.com/x/raw/upload/v1/r-1');

        $this->actingAs($owner)
            ->getJson($this->url($application))
            ->assertStatus(502);
    }
}
