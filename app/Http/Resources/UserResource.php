<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];

        if ($this->relationLoaded('candidateProfile')) {
            $data['profile'] = new CandidateProfileResource($this->candidateProfile);
        }

        if ($this->relationLoaded('employerProfile')) {
            $data['profile'] = new EmployerProfileResource($this->employerProfile);
        }

        return $data;
    }
}
