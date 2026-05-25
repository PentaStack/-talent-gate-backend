<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\ApplicationPolicy;
use PHPUnit\Framework\TestCase;

class ApplicationPolicyTest extends TestCase
{
    private ApplicationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ApplicationPolicy;
    }

    private function makeUser(string $role, bool $banned = false): User
    {
        $user = new User;
        $user->role = $role;
        $user->banned = $banned;

        return $user;
    }

    // ── create() ─────────────────────────────────────────────────────────────

    public function test_create_returns_true_for_active_candidate(): void
    {
        $this->assertTrue($this->policy->create($this->makeUser('candidate')));
    }

    public function test_create_returns_false_for_banned_candidate(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser('candidate', banned: true)));
    }

    public function test_create_returns_false_for_employer(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser('employer')));
    }

    public function test_create_returns_false_for_admin(): void
    {
        $this->assertFalse($this->policy->create($this->makeUser('admin')));
    }
}
