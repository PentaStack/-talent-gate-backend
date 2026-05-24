<?php

namespace App\Http\Requests\Profile;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();

        if ($user && $user->role === 'candidate') {
            return [
                'name' => ['required', 'string', 'max:255'],
                'bio' => ['nullable', 'string'],
                'skills' => ['nullable', 'array'],
                'skills.*' => ['string'],
                'experience_level' => ['nullable', 'in:junior,mid,senior'],
            ];
        }

        // employer
        return [
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
