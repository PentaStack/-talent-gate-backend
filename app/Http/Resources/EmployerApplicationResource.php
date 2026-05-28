<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployerApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status->value,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'viewed_at'    => $this->viewed_at?->toIso8601String(),
            'cover_letter' => $this->cover_letter,
            'candidate'    => $this->whenLoaded('candidate', fn () => [
                'id'               => $this->candidate->id,
                'name'             => $this->candidate->name,
                'experience_level' => $this->candidate->candidateProfile?->experience_level,
                'skills'           => $this->candidate->candidateProfile?->skills,
                'avatar_url'       => $this->candidate->candidateProfile?->avatar_full_url,
            ]),
        ];
    }
}
