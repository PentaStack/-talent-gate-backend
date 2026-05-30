<?php

namespace App\Http\Controllers\Search;

use App\Enums\JobStatus;
use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Models\Location;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search / filter active job listings.
     *
     * Accepted query parameters:
     *   q              – keyword (title or description)
     *   sort           – newest (default) | deadline
     *   page           – page number
     *   per_page       – 1–100, default 20
     *   experience_level – entry | mid | senior | lead
     *   location       – partial string match on location column
     *   work_type      – remote | onsite | hybrid
     *   category       – category slug or numeric id
     *   date_from      – YYYY-MM-DD, filter jobs posted on/after this date
     */
    public function jobs(Request $request): JsonResponse
    {
        $query = Job::where('status', JobStatus::Active)
            ->where('application_deadline', '>=', now()->toDateString())
            ->with(['employer.employerProfile', 'category', 'technologies']);

        // Keyword search
        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($sub) use ($q): void {
                $term = '%'.mb_strtolower($q).'%';
                $sub->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(description) LIKE ?', [$term]);
            });
        }

        // Experience level
        if ($level = $request->input('experience_level')) {
            $query->where('experience_level', $level);
        }

        // Location (partial, case-insensitive)
        if ($location = trim((string) $request->input('location', ''))) {
            $query->whereRaw('LOWER(location) LIKE ?', ['%'.mb_strtolower($location).'%']);
        }

        // Work type
        if ($workType = $request->input('work_type')) {
            $query->where('work_type', $workType);
        }

        // Category (slug or numeric id)
        if ($category = $request->input('category')) {
            $query->whereHas('category', function ($sub) use ($category): void {
                is_numeric($category)
                    ? $sub->where('id', $category)
                    : $sub->where('slug', $category);
            });
        }

        // Date posted from
        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }

        // Sorting
        match ($request->input('sort', 'newest')) {
            'deadline' => $query->orderBy('application_deadline'),
            default    => $query->latest(),
        };

        $perPage = min(max(1, (int) $request->input('per_page', 20)), 100);
        $jobs = $query->paginate($perPage);

        return response()->json([
            'data' => $jobs->map(fn (Job $job) => [
                'id'                   => $job->id,
                'title'                => $job->title,
                'description'          => $job->description,
                'salary_range'         => $job->salary_range,
                'work_type'            => $job->work_type?->value,
                'location'             => $job->location,
                'experience_level'     => $job->experience_level?->value,
                'application_deadline' => $job->application_deadline?->toDateString(),
                'created_at'           => $job->created_at?->toDateString(),
                'category'             => $job->category
                    ? ['id' => $job->category->id, 'name' => $job->category->name, 'slug' => $job->category->slug]
                    : null,
                'technologies' => $job->technologies
                    ->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])
                    ->values(),
                'employer' => [
                    'company_name' => $job->employer?->employerProfile?->company_name ?? 'Unknown',
                    'logo_url'     => $job->employer?->employerProfile?->logo_url,
                ],
            ])->values(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page'    => $jobs->lastPage(),
                'per_page'     => $jobs->perPage(),
                'total'        => $jobs->total(),
            ],
        ]);
    }

    /**
     * Return the seeded location reference list for autocomplete.
     */
    public function locations(): JsonResponse
    {
        return response()->json([
            'data' => Location::orderBy('name')->get(['id', 'name', 'slug']),
        ]);
    }
}
