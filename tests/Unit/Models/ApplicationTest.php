<?php

namespace Tests\Unit\Models;

use App\Enums\ApplicationStatus;
use App\Models\Application;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_submitted_at_is_set_automatically_on_creating(): void
    {
        $application = Application::factory()->create();

        $this->assertNotNull($application->submitted_at);
    }

    public function test_submitted_at_is_not_in_fillable(): void
    {
        $this->assertNotContains('submitted_at', (new Application)->getFillable());
    }

    public function test_cover_letter_is_in_fillable(): void
    {
        $this->assertContains('cover_letter', (new Application)->getFillable());
    }

    public function test_candidate_id_is_not_overridden_by_mass_assignment_with_submitted_at(): void
    {
        $application = Application::factory()->make();
        $before = now()->subSecond();

        $application->fill(['submitted_at' => '2000-01-01 00:00:00']);
        $application->save();

        $this->assertTrue($application->fresh()->submitted_at->greaterThan($before));
    }

    public function test_status_is_cast_to_application_status_enum(): void
    {
        $application = Application::factory()->create(['status' => ApplicationStatus::Pending]);

        $this->assertInstanceOf(ApplicationStatus::class, $application->status);
        $this->assertSame(ApplicationStatus::Pending, $application->status);
    }

    public function test_submitted_at_is_cast_to_datetime(): void
    {
        $application = Application::factory()->create();

        $this->assertInstanceOf(Carbon::class, $application->submitted_at);
    }

    public function test_viewed_at_is_null_by_default(): void
    {
        $application = Application::factory()->create();

        $this->assertNull($application->viewed_at);
    }

    public function test_notes_is_in_fillable(): void
    {
        $this->assertContains('notes', (new Application)->getFillable());
    }

    public function test_notes_persists_and_reads_back(): void
    {
        $application = Application::factory()->create(['notes' => 'Strong candidate.']);

        $this->assertSame('Strong candidate.', $application->fresh()->notes);
    }

    public function test_notes_is_null_by_default(): void
    {
        $application = Application::factory()->create();

        $this->assertNull($application->notes);
    }
}
