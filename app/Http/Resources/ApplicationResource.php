<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status->value,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'viewed_at'    => $this->viewed_at?->toIso8601String(),
            'job'          => $this->whenLoaded('job', fn () => [
                'id'       => $this->job->id,
                'title'    => $this->job->title,
                'employer' => [
                    'company_name' => $this->job->employer->employerProfile?->company_name,
                    'logo_url'     => $this->job->employer->employerProfile?->logo_full_url,
                ],
            ]),
        ];
    }
}
