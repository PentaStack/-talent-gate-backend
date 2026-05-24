<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'bio', 'skills', 'resume_url', 'avatar_url', 'experience_level',
    ];

    protected $casts = [
        'skills' => 'array',
    ];

    protected $appends = [
        'resume_full_url',
        'avatar_full_url',
    ];

    public function getResumeFullUrlAttribute()
    {
        if (!$this->resume_url) {
            return null;
        }
        return str_starts_with($this->resume_url, 'http')
            ? $this->resume_url
            : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->resume_url);
    }

    public function getAvatarFullUrlAttribute()
    {
        if (!$this->avatar_url) {
            return null;
        }
        return str_starts_with($this->avatar_url, 'http')
            ? $this->avatar_url
            : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->avatar_url);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
