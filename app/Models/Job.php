<?php

namespace App\Models;

use App\Enums\JobStatus;
use App\Enums\WorkType;
use Database\Factories\JobFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Job extends Model
{
    /** @use HasFactory<JobFactory> */
    use HasFactory;

    protected $table = 'job_listings';

    protected $fillable = [
        'employer_id', 'title', 'description', 'requirements',
        'salary_range', 'work_type', 'location', 'category_id',
        'status', 'rejection_reason', 'views_count', 'application_deadline',
    ];

    protected function casts(): array
    {
        return [
            'status'               => JobStatus::class,
            'work_type'            => WorkType::class,
            'application_deadline' => 'date',
        ];
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function technologies(): BelongsToMany
    {
        return $this->belongsToMany(Technology::class, 'job_technology');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(JobComment::class);
    }
}

