<?php

namespace Tests\Feature\Api\V1\Employer;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Notifications\ApplicationViewedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ViewApplicationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function url(Application $application): string
    {
        return "/api/v1/employer/applications/{$application->id}";
    }

    public function test_candidate_not_notified_on_subsequent_views(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($employer)->create();
        $application = Application::factory()->for($job)->create(['viewed_at' => now()]);

        $this->actingAs($employer)
            ->getJson($this->url($application))
            ->assertOk();

        Notification::assertNotSentTo($application->candidate, ApplicationViewedNotification::class);
    }

    public function test_notification_payload_contains_required_fields(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($employer)->create();
        $application = Application::factory()->for($job)->create(['viewed_at' => null]);

        $this->actingAs($employer)
            ->getJson($this->url($application))
            ->assertOk();

        Notification::assertSentTo(
            $application->candidate,
            ApplicationViewedNotification::class,
            function ($n) use ($application) {
                $payload = $n->toArray($application->candidate);

                return $payload['application_id'] === $application->id
                    && $payload['job_title'] === $application->job->title
                    && $payload['message'] === "Your application for {$application->job->title} has been viewed by the employer.";
            }
        );
    }

    public function test_cross_employer_cannot_trigger_notification(): void
    {
        Notification::fake();

        $ownerEmployer = User::factory()->employer()->create();
        $otherEmployer = User::factory()->employer()->create();
        $job           = Job::factory()->forEmployer($ownerEmployer)->create();
        $application   = Application::factory()->for($job)->create(['viewed_at' => null]);

        $this->actingAs($otherEmployer)
            ->getJson($this->url($application))
            ->assertForbidden();

        Notification::assertNotSentTo($application->candidate, ApplicationViewedNotification::class);
    }

    public function test_candidate_notified_on_first_view(): void
    {
        Notification::fake();

        $employer    = User::factory()->employer()->create();
        $job         = Job::factory()->forEmployer($employer)->create();
        $application = Application::factory()->for($job)->create(['viewed_at' => null]);

        $this->actingAs($employer)
            ->getJson($this->url($application))
            ->assertOk();

        Notification::assertSentTo(
            $application->candidate,
            ApplicationViewedNotification::class,
            fn ($n) => $n->applicationId === $application->id
                    && $n->jobTitle === $application->job->title
        );
    }
}
