<?php

namespace App\Http\Requests;

use App\Enums\WorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class StoreJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                => ['required', 'string', 'max:255'],
            'description'          => ['required', 'string'],
            'requirements'         => ['nullable', 'string'],
            'salary_range'         => ['nullable', 'string', 'max:100'],
            'work_type'            => ['required', new Enum(WorkType::class)],
            'location'             => ['nullable', 'string', 'max:255'],
            'category_id'          => ['nullable', 'integer', 'exists:categories,id'],
            'application_deadline' => ['required', 'date', 'after:today'],
            'technology_ids'       => ['nullable', 'array'],
            'technology_ids.*'     => ['integer', 'exists:technologies,id'],
            'status'               => ['nullable', 'string', 'in:draft,pending'],
        ];
    }
}
