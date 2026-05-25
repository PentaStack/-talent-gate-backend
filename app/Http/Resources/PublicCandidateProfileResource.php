<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PublicCandidateProfileResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'bio' => $this->bio,
            'skills' => $this->skills,
            'experience_level' => $this->experience_level,
            'avatar_url' => $this->avatar_full_url,
        ];
    }
}
