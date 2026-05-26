<?php

namespace Tests\Unit\Policies;

use App\Models\Application;
use App\Models\Job;
use App\Models\User;
use App\Policies\ApplicationPolicy;
use PHPUnit\Framework\TestCase;

class ApplicationPolicyEmployerTest extends TestCase
{
    private ApplicationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ApplicationPolicy;
    }

    private function makeUser(int $id): User
    {
        $user = new User;
        $user->id = $id;

        return $user;
    }

    private function makeJob(int $employerId): Job
    {
        $job = new Job;
        $job->employer_id = $employerId;

        return $job;
    }

    private function makeApplication(int $jobEmployerId): Application
    {
        $application = new Application;
        $application->setRelation('job', $this->makeJob($jobEmployerId));

        return $application;
    }

    // ── viewAnyForJob() ───────────────────────────────────────────────────────

    public function test_viewAnyForJob_returns_true_when_employer_owns_job(): void
    {
        $this->assertTrue(
            $this->policy->viewAnyForJob($this->makeUser(1), $this->makeJob(1))
        );
    }

    public function test_viewAnyForJob_returns_false_for_different_employer(): void
    {
        $this->assertFalse(
            $this->policy->viewAnyForJob($this->makeUser(1), $this->makeJob(2))
        );
    }

    // ── viewEmployer() ────────────────────────────────────────────────────────

    public function test_viewEmployer_returns_true_when_employer_owns_applications_job(): void
    {
        $this->assertTrue(
            $this->policy->viewEmployer($this->makeUser(1), $this->makeApplication(1))
        );
    }

    public function test_viewEmployer_returns_false_for_different_employer(): void
    {
        $this->assertFalse(
            $this->policy->viewEmployer($this->makeUser(1), $this->makeApplication(2))
        );
    }
}
