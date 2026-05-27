<?php

namespace App\Http\Requests;

use App\Enums\ApplicationStatus;
use App\Enums\JobStatus;
use App\Models\Application;
use App\Models\Job;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'cover_letter' => ['required', 'string', 'min:1', 'max:5000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            /** @var Job $job */
            $job = $this->route('job');

            if ($job->status !== JobStatus::Active) {
                $v->errors()->add('job', 'This job listing is not accepting applications.');

                return;
            }

            if ($job->application_deadline->lt(now('UTC')->startOfDay())) {
                $v->errors()->add('job', 'The application deadline for this job has passed.');

                return;
            }

            $existing = Application::query()
                ->where('job_id', $job->id)
                ->where('candidate_id', $this->user()->id)
                ->first();

            if ($existing === null) {
                return;
            }

            if ($existing->status === ApplicationStatus::Withdrawn) {
                $v->errors()->add(
                    'application',
                    'You previously withdrew your application for this job and cannot re-apply.'
                );
            } else {
                $v->errors()->add('application', 'You have already applied for this job.');
            }
        });
    }
}
