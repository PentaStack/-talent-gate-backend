<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JobResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->id,
            'title'                => $this->title,
            'description'          => $this->description,
            'requirements'         => $this->requirements,
            'salary_range'         => $this->salary_range,
            'work_type'            => $this->work_type?->value,
            'location'             => $this->location,
            'status'               => $this->status->value,
            'application_deadline' => $this->application_deadline?->toDateString(),
            'views_count'          => $this->views_count,
            'category'             => $this->whenLoaded('category', fn () => [
                'id'   => $this->category->id,
                'name' => $this->category->name,
            ]),
            'technologies' => $this->whenLoaded('technologies', fn () =>
                $this->technologies->map(fn ($t) => ['id' => $t->id, 'name' => $t->name])->values()
            ),
            'employer' => $this->whenLoaded('employer', fn () => [
                'company_name' => $this->employer->employerProfile?->company_name ?? $this->employer->name,
                'logo_url'     => $this->employer->employerProfile?->logo_full_url,
            ]),
            'applications_count' => $this->whenCounted('applications'),
            'created_at'         => $this->created_at?->toDateString(),
        ];
    }
}
