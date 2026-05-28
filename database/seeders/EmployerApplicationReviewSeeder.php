<?php

namespace Database\Seeders;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\Job;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EmployerApplicationReviewSeeder extends Seeder
{
    public function run(): void
    {
        // ── Users ─────────────────────────────────────────────────────────────

        $employer = User::query()->updateOrCreate(
            ['email' => 'employer@talentgate.test'],
            ['name' => 'Employer Co', 'role' => 'employer', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $employer2 = User::query()->updateOrCreate(
            ['email' => 'employer2@talentgate.test'],
            ['name' => 'Second Corp', 'role' => 'employer', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $candidate = User::query()->updateOrCreate(
            ['email' => 'candidate@talentgate.test'],
            ['name' => 'Jane Candidate', 'role' => 'candidate', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        $candidate2 = User::query()->updateOrCreate(
            ['email' => 'candidate2@talentgate.test'],
            ['name' => 'Bob Applicant', 'role' => 'candidate', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );

        // ── Employer profiles ──────────────────────────────────────────────────

        EmployerProfile::query()->updateOrCreate(
            ['user_id' => $employer->id],
            ['company_name' => 'Employer Co', 'website' => 'https://employer.co', 'description' => 'A great place to work.']
        );

        EmployerProfile::query()->updateOrCreate(
            ['user_id' => $employer2->id],
            ['company_name' => 'Second Corp', 'website' => 'https://second.corp', 'description' => 'Another company.']
        );

        // ── Candidate profiles ─────────────────────────────────────────────────

        CandidateProfile::query()->updateOrCreate(
            ['user_id' => $candidate->id],
            ['bio' => 'Experienced backend developer.', 'skills' => ['PHP', 'Laravel', 'PostgreSQL'], 'experience_level' => 'mid']
        );

        // candidate2 intentionally has no profile — tests null-field handling

        // ── Jobs ───────────────────────────────────────────────────────────────

        $activeJob = Job::query()->updateOrCreate(
            ['employer_id' => $employer->id, 'title' => 'Senior PHP Developer'],
            ['status' => JobStatus::Active, 'application_deadline' => now()->addYear()->toDateString()]
        );

        Job::query()->updateOrCreate(
            ['employer_id' => $employer->id, 'title' => 'Junior DevOps Engineer'],
            ['status' => JobStatus::Closed, 'application_deadline' => now()->subDay()->toDateString()]
        );

        $employer2Job = Job::query()->updateOrCreate(
            ['employer_id' => $employer2->id, 'title' => 'Frontend Engineer'],
            ['status' => JobStatus::Active, 'application_deadline' => now()->addMonths(3)->toDateString()]
        );

        // ── Applications ───────────────────────────────────────────────────────

        // candidate → employer's active job: pending, unread
        Application::query()->updateOrCreate(
            ['job_id' => $activeJob->id, 'candidate_id' => $candidate->id],
            ['status' => ApplicationStatus::Pending, 'cover_letter' => 'I am very interested in this role and have 4 years of Laravel experience.', 'viewed_at' => null]
        );

        // candidate2 → employer's active job: shortlisted, already read
        Application::query()->updateOrCreate(
            ['job_id' => $activeJob->id, 'candidate_id' => $candidate2->id],
            ['status' => ApplicationStatus::Shortlisted, 'cover_letter' => 'Excited to join your team. I have shipped several production apps.', 'viewed_at' => now()->subHour()]
        );

        // candidate → employer2's job: tests IDOR guard (employer cannot see this)
        Application::query()->updateOrCreate(
            ['job_id' => $employer2Job->id, 'candidate_id' => $candidate->id],
            ['status' => ApplicationStatus::Pending, 'cover_letter' => 'Great opportunity, applying here too.', 'viewed_at' => null]
        );
    }
}
