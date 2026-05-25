<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\ResetPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\User;

class PasswordResetController extends Controller
{
    public function forgot(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::sendResetLink($request->only('email'));
        } catch (\Throwable $exception) {
            Log::error('Failed to send password reset link.', [
                'email' => $request->input('email'),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to send the reset link right now. Please try again.',
            ], 500);
        }

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Reset link sent']);
        }

        Log::warning('Password reset link request returned non-success status.', [
            'email' => $request->input('email'),
            'status' => $status,
        ]);

        return response()->json(['message' => __($status)], 400);
    }

    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        try {
            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function (User $user, string $password) {
                    $user->forceFill([
                        'password' => $password,
                        'remember_token' => Str::random(60),
                    ])->save();
                }
            );
        } catch (\Throwable $exception) {
            Log::error('Failed to reset password.', [
                'email' => $request->input('email'),
                'error' => $exception->getMessage(),
            ]);

            return response()->json([
                'message' => 'Unable to reset password right now. Please try again.',
            ], 500);
        }

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Password reset']);
        }

        Log::warning('Password reset failed with broker status.', [
            'email' => $request->input('email'),
            'status' => $status,
        ]);

        return response()->json(['message' => __($status)], 400);
    }
}
