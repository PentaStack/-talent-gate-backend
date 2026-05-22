<?php

use App\Http\Controllers\Admin\AdminStatsController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Admin\EmployerAnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Notification\NotificationController;
use App\Http\Controllers\Payment\PaymentController;
use App\Http\Controllers\Payment\PayPalController;
use App\Http\Controllers\Payment\StripeWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('login', [AuthController::class, 'login']);
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
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
