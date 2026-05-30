<?php

namespace App\Http\Requests;

use App\Enums\WorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;

class UpdateJobRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'                => ['sometimes', 'required', 'string', 'max:255'],
            'description'          => ['sometimes', 'required', 'string'],
            'requirements'         => ['sometimes', 'nullable', 'string'],
            'salary_range'         => ['sometimes', 'nullable', 'string', 'max:100'],
            'work_type'            => ['sometimes', 'required', new Enum(WorkType::class)],
            'location'             => ['sometimes', 'nullable', 'string', 'max:255'],
            'category_id'          => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
            'application_deadline' => ['sometimes', 'required', 'date'],
            'technology_ids'       => ['sometimes', 'nullable', 'array'],
            'technology_ids.*'     => ['integer', 'exists:technologies,id'],
            'status'               => ['sometimes', 'nullable', 'string', 'in:draft,pending'],
        ];
    }
}
