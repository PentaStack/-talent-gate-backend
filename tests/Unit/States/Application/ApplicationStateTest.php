<?php

namespace Tests\Unit\States\Application;

use App\Enums\ApplicationStatus;
use PHPUnit\Framework\TestCase;

class ApplicationStateTest extends TestCase
{
    // ── Candidate: allowed withdrawals ───────────────────────────────────────

    public function test_pending_state_allows_candidate_to_withdraw(): void
    {
        $this->assertTrue(
            ApplicationStatus::Pending->toState()->canTransitionTo(ApplicationStatus::Withdrawn, 'candidate')
        );
    }

    public function test_shortlisted_state_allows_candidate_to_withdraw(): void
    {
        $this->assertTrue(
            ApplicationStatus::Shortlisted->toState()->canTransitionTo(ApplicationStatus::Withdrawn, 'candidate')
        );
    }

    // ── Candidate: wrong-role (cannot use employer transitions) ─────────────

    public function test_pending_state_denies_candidate_shortlisting(): void
    {
        $this->assertFalse(
            ApplicationStatus::Pending->toState()->canTransitionTo(ApplicationStatus::Shortlisted, 'candidate')
        );
    }

    public function test_employer_cannot_withdraw(): void
    {
        $this->assertFalse(
            ApplicationStatus::Pending->toState()->canTransitionTo(ApplicationStatus::Withdrawn, 'employer')
        );
    }

    // ── Employer: allowed transitions from pending ───────────────────────────

    public function test_pending_state_allows_employer_to_shortlist(): void
    {
        $this->assertTrue(
            ApplicationStatus::Pending->toState()->canTransitionTo(ApplicationStatus::Shortlisted, 'employer')
        );
    }

    public function test_pending_state_allows_employer_to_accept(): void
    {
        $this->assertTrue(
            ApplicationStatus::Pending->toState()->canTransitionTo(ApplicationStatus::Accepted, 'employer')
        );
    }

    public function test_pending_state_allows_employer_to_reject(): void
    {
        $this->assertTrue(
            ApplicationStatus::Pending->toState()->canTransitionTo(ApplicationStatus::Rejected, 'employer')
        );
    }

    // ── Employer: allowed transitions from shortlisted ───────────────────────

    public function test_shortlisted_state_allows_employer_to_accept(): void
    {
        $this->assertTrue(
            ApplicationStatus::Shortlisted->toState()->canTransitionTo(ApplicationStatus::Accepted, 'employer')
        );
    }

    public function test_shortlisted_state_allows_employer_to_reject(): void
    {
        $this->assertTrue(
            ApplicationStatus::Shortlisted->toState()->canTransitionTo(ApplicationStatus::Rejected, 'employer')
        );
    }

    // ── Employer: same-status is always denied ───────────────────────────────

    public function test_shortlisted_state_denies_employer_shortlisting_again(): void
    {
        $this->assertFalse(
            ApplicationStatus::Shortlisted->toState()->canTransitionTo(ApplicationStatus::Shortlisted, 'employer')
        );
    }

    // ── Terminal states: always deny any transition ──────────────────────────

    public function test_accepted_state_denies_any_transition_for_candidate(): void
    {
        $this->assertFalse(
            ApplicationStatus::Accepted->toState()->canTransitionTo(ApplicationStatus::Withdrawn, 'candidate')
        );
    }

    public function test_accepted_state_denies_any_transition_for_employer(): void
    {
        $this->assertFalse(
            ApplicationStatus::Accepted->toState()->canTransitionTo(ApplicationStatus::Rejected, 'employer')
        );
    }

    public function test_rejected_state_denies_any_transition(): void
    {
        $this->assertFalse(
            ApplicationStatus::Rejected->toState()->canTransitionTo(ApplicationStatus::Withdrawn, 'candidate')
        );
    }

    public function test_withdrawn_state_denies_any_transition(): void
    {
        $this->assertFalse(
            ApplicationStatus::Withdrawn->toState()->canTransitionTo(ApplicationStatus::Withdrawn, 'candidate')
        );
    }
}
