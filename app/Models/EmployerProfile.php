<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployerProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'company_name', 'logo_url', 'website', 'description',
    ];

    protected $appends = [
        'logo_full_url',
    ];

    public function getLogoFullUrlAttribute()
    {
        if (!$this->logo_url) {
            return null;
        }
        return str_starts_with($this->logo_url, 'http')
            ? $this->logo_url
            : \Illuminate\Support\Facades\Storage::disk(config('filesystems.default'))->url($this->logo_url);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
