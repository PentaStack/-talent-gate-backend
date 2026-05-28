<?php

namespace Tests\Feature\Api\V1;

use App\Models\Application;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListApplicationsTest extends TestCase
{
    use RefreshDatabase;

    private function url(): string
    {
        return '/api/v1/applications';
    }

    // ── B1: tracer bullet ────────────────────────────────────────────────────

    public function test_authenticated_candidate_gets_200_with_paginated_structure(): void
    {
        $candidate = User::factory()->candidate()->create();
        Application::factory()->for($candidate, 'candidate')->create();

        $response = $this->actingAs($candidate)->getJson($this->url());

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'meta']);
    }

    // ── B2: response shape ───────────────────────────────────────────────────

    public function test_response_includes_correct_fields_and_excludes_cover_letter(): void
    {
        $employer = User::factory()->employer()->create();
        EmployerProfile::factory()->for($employer, 'user')->create();
        $job = Job::factory()->forEmployer($employer)->create();
        $candidate = User::factory()->candidate()->create();
        Application::factory()->for($candidate, 'candidate')->for($job)->create();

        $response = $this->actingAs($candidate)->getJson($this->url());

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [[
                    'id', 'status', 'submitted_at', 'viewed_at',
                    'job' => ['id', 'title', 'employer' => ['company_name', 'logo_url']],
                ]],
            ]);

        $this->assertArrayNotHasKey('cover_letter', $response->json('data.0'));
    }

    // ── B3: isolation ────────────────────────────────────────────────────────

    public function test_list_returns_only_own_applications(): void
    {
        $candidate = User::factory()->candidate()->create();
        $other     = User::factory()->candidate()->create();

        Application::factory()->for($candidate, 'candidate')->create();
        Application::factory()->for($other, 'candidate')->create();

        $response = $this->actingAs($candidate)->getJson($this->url());

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    // ── B4: pagination ───────────────────────────────────────────────────────

    public function test_list_is_paginated_15_per_page(): void
    {
        $candidate = User::factory()->candidate()->create();
        Application::factory(16)->for($candidate, 'candidate')->create();

        $response = $this->actingAs($candidate)->getJson($this->url());

        $response->assertStatus(200);
        $this->assertCount(15, $response->json('data'));
        $this->assertSame(2, $response->json('meta.last_page'));
    }

    // ── B5–B6: auth / role gates ─────────────────────────────────────────────

    public function test_unauthenticated_gets_401(): void
    {
        $this->getJson($this->url())->assertStatus(401);
    }

    public function test_employer_role_gets_403(): void
    {
        $employer = User::factory()->employer()->create();
        $this->actingAs($employer)->getJson($this->url())->assertStatus(403);
    }
}
