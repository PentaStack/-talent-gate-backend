<?php

namespace Tests\Feature\Api\V1\Employer;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ListEmployerApplicationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function url(Job $job): string
    {
        return "/api/v1/employer/jobs/{$job->id}/applications";
    }

    // ── auth & role gates ─────────────────────────────────────────────────────

    public function test_unauthenticated_gets_401(): void
    {
        $job = Job::factory()->create();

        $this->getJson($this->url($job))->assertStatus(401);
    }

    public function test_candidate_role_gets_403(): void
    {
        $candidate = User::factory()->candidate()->create();
        $job       = Job::factory()->create();

        $this->actingAs($candidate)->getJson($this->url($job))->assertStatus(403);
    }

    // ── authorization: job ownership ─────────────────────────────────────────

    public function test_employer_gets_403_for_another_employers_job(): void
    {
        $owner = User::factory()->employer()->create();
        $other = User::factory()->employer()->create();
        $job   = Job::factory()->forEmployer($owner)->create();

        $this->actingAs($other)
            ->getJson($this->url($job))
            ->assertStatus(403);
    }

    // ── happy path ────────────────────────────────────────────────────────────

    public function test_authenticated_employer_gets_200_for_own_job_applications(): void
    {
        $employer = User::factory()->employer()->create();
        $job      = Job::factory()->forEmployer($employer)->create();
        Application::factory()->for($job)->create();

        $this->actingAs($employer)
            ->getJson($this->url($job))
            ->assertStatus(200);
    }

    public function test_list_response_has_correct_shape(): void
    {
        $employer  = User::factory()->employer()->create();
        $job       = Job::factory()->forEmployer($employer)->create();
        $candidate = User::factory()->candidate()->create();
        CandidateProfile::factory()->for($candidate, 'user')->create();
        Application::factory()->for($job)->for($candidate, 'candidate')->create();

        $response = $this->actingAs($employer)->getJson($this->url($job));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'status', 'submitted_at', 'viewed_at', 'cover_letter',
                    'candidate' => ['id', 'name', 'experience_level', 'skills', 'avatar_url'],
                ]],
            ]);
    }

    // ── status filter ─────────────────────────────────────────────────────────

    public function test_status_filter_returns_only_matching_applications(): void
    {
        $employer  = User::factory()->employer()->create();
        $job       = Job::factory()->forEmployer($employer)->create();
        Application::factory()->for($job)->create(['status' => ApplicationStatus::Pending]);
        Application::factory()->for($job)->create(['status' => ApplicationStatus::Shortlisted]);

        $response = $this->actingAs($employer)
            ->getJson($this->url($job) . '?status=pending');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertSame('pending', $response->json('data.0.status'));
    }

    public function test_invalid_status_filter_returns_422(): void
    {
        $employer = User::factory()->employer()->create();
        $job      = Job::factory()->forEmployer($employer)->create();

        $this->actingAs($employer)
            ->getJson($this->url($job) . '?status=garbage')
            ->assertStatus(422);
    }

    public function test_absent_status_filter_returns_all_applications(): void
    {
        $employer = User::factory()->employer()->create();
        $job      = Job::factory()->forEmployer($employer)->create();
        Application::factory()->for($job)->create(['status' => ApplicationStatus::Pending]);
        Application::factory()->for($job)->create(['status' => ApplicationStatus::Shortlisted]);

        $response = $this->actingAs($employer)->getJson($this->url($job));

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('data'));
    }

    // ── pagination ────────────────────────────────────────────────────────────

    public function test_list_is_paginated_15_per_page_by_default(): void
    {
        $employer = User::factory()->employer()->create();
        $job      = Job::factory()->forEmployer($employer)->create();
        Application::factory(16)->for($job)->create();

        $response = $this->actingAs($employer)->getJson($this->url($job));

        $response->assertStatus(200);
        $this->assertCount(15, $response->json('data'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    public function test_custom_per_page_is_respected(): void
    {
        $employer = User::factory()->employer()->create();
        $job      = Job::factory()->forEmployer($employer)->create();
        Application::factory(10)->for($job)->create();

        $response = $this->actingAs($employer)
            ->getJson($this->url($job) . '?per_page=5');

        $response->assertStatus(200);
        $this->assertCount(5, $response->json('data'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    // ── edge cases ────────────────────────────────────────────────────────────

    public function test_candidate_with_no_profile_returns_null_fields_not_500(): void
    {
        $employer  = User::factory()->employer()->create();
        $job       = Job::factory()->forEmployer($employer)->create();
        $candidate = User::factory()->candidate()->create();
        Application::factory()->for($job)->for($candidate, 'candidate')->create();

        $response = $this->actingAs($employer)->getJson($this->url($job));

        $response->assertStatus(200);
        $this->assertNull($response->json('data.0.candidate.experience_level'));
        $this->assertNull($response->json('data.0.candidate.skills'));
        $this->assertNull($response->json('data.0.candidate.avatar_url'));
    }
}
