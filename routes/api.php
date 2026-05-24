<?php

use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\EmployerAnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Payment\PayPalController;
use App\Http\Controllers\Payment\StripeWebhookController;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::post('register', [\App\Http\Controllers\Auth\RegisterController::class, 'register']);
Route::post('forgot-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'forgot']);
Route::post('reset-password', [\App\Http\Controllers\Auth\PasswordResetController::class, 'reset']);
Route::post('email/verification-notification', function (Request $request) {
    $data = $request->validate([
        'email' => ['required', 'email'],
    ]);

    $user = User::where('email', $data['email'])->first();

    if (! $user) {
        return response()->json([
            'message' => 'If this account exists, a verification email has been sent.',
        ]);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json([
            'message' => 'Email is already verified.',
        ]);
    }

    try {
        $user->sendEmailVerificationNotification();
    } catch (\Throwable $exception) {
        Log::error('Failed to resend verification email.', [
            'user_id' => $user->id,
            'email' => $user->email,
            'error' => $exception->getMessage(),
        ]);

        return response()->json([
            'message' => 'Unable to send verification email right now. Please try again later.',
        ], 500);
    }

    return response()->json([
        'message' => 'Verification email sent.',
    ]);
})->middleware('throttle:6,1');

Route::get('email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    $user = User::findOrFail($id);

    if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
        abort(403);
    }

    if (! $request->hasValidSignature()) {
        abort(403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    $frontendLoginUrl = rtrim((string) config('app.frontend_url'), '/').'/login?verified=1';

    return redirect()->away($frontendLoginUrl);
})->middleware('signed')->name('verification.verify');

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    
    // Profile routes v1
    Route::prefix('v1')->group(function () {
        Route::prefix('profile')->group(function () {
            Route::middleware('auth')->group(function () {
                Route::get('/', [\App\Http\Controllers\Api\V1\Profile\ProfileController::class, 'index']);
                Route::put('/', [\App\Http\Controllers\Api\V1\Profile\ProfileController::class, 'update']);
                Route::post('/avatar', [\App\Http\Controllers\Api\V1\Profile\AvatarUploadController::class, 'store']);
                Route::post('/resume', [\App\Http\Controllers\Api\V1\Profile\ResumeUploadController::class, 'store'])->middleware('role:candidate');
                Route::delete('/resume', [\App\Http\Controllers\Api\V1\Profile\ResumeUploadController::class, 'destroy'])->middleware('role:candidate');
                Route::get('/{user}/resume-link', [\App\Http\Controllers\Api\V1\Profile\ProfileController::class, 'resumeLink'])->middleware('role:employer');
            });
    
            // public candidate profile
            Route::get('/{user}', [\App\Http\Controllers\Api\V1\Profile\ProfileController::class, 'showPublic']);
        });
    });
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('stats', AdminStatsController::class);
    Route::get('users', [AdminUserController::class, 'index']);
    Route::patch('users/{user}/ban', [AdminUserController::class, 'ban']);
    Route::patch('users/{user}', [AdminUserController::class, 'update']);
});

Route::middleware(['auth', 'role:employer'])->prefix('employer')->group(function () {
    Route::get('analytics', EmployerAnalyticsController::class);
});

Route::post('payments/stripe/webhook', StripeWebhookController::class);

Route::middleware(['auth', 'role:employer'])->group(function () {
    Route::get('payments', [PaymentController::class, 'index']);
    Route::post('payments/paypal', [PayPalController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
});
