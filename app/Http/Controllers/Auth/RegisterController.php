<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Mail\WelcomeEmail;
use App\Models\CandidateProfile;
use App\Models\EmployerProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class RegisterController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = DB::transaction(function () use ($request) {
                $data = $request->only(['name', 'email', 'password', 'role']);

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => $data['role'],
                ]);

                if ($user->role === 'candidate') {
                    CandidateProfile::create(['user_id' => $user->id]);
                } else {
                    EmployerProfile::create(['user_id' => $user->id, 'company_name' => '']);
                }

                // New accounts must verify email before first login.
                $user->sendEmailVerificationNotification();

                return $user;
            });
        } catch (Throwable $exception) {
            Log::error('Failed to complete registration.', [
                'email' => $request->input('email'),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to complete registration. Please try again shortly.',
            ], 500);
        }

        // Queue welcome email
        try {
            Mail::to($user->email)->queue(new WelcomeEmail($user));
        } catch (Throwable $exception) {
            Log::error('Failed to queue welcome email after registration.', [
                'user_id' => $user->id,
                'email' => $user->email,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Registration successful. Please verify your email before logging in.',
            'user' => $this->userPayload($user),
        ], 201);
    }

    private function userPayload(User $user): array
    {
        $payload = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ];

        if ($user->role === 'candidate') {
            $payload['candidate_profile'] = $user->candidateProfile?->toArray();
        }

        if ($user->role === 'employer') {
            $payload['employer_profile'] = $user->employerProfile?->toArray();
        }

        return $payload;
    }
}
