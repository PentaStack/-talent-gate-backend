<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technology extends Model
{
    protected $fillable = ['name', 'slug'];

    public function jobs(): BelongsToMany
    {
        return $this->belongsToMany(Job::class, 'job_technology');
    }
}
