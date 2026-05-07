<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PaperController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\NotificationController;

// Public routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'forgotPassword']);
Route::post('/reset-password', [\App\Http\Controllers\Api\PasswordResetController::class, 'resetPassword']);

// Public papers listing (published only)
Route::get('/publications', [PaperController::class, 'publicIndex']);
Route::get('/publications/{paper}', [PaperController::class, 'show']);
Route::get('/publications/{paper}/download', [PaperController::class, 'download']);
Route::get('/publications/{paper}/download-word', [PaperController::class, 'downloadWord']);

// Public Settings Route
Route::get('/settings', [\App\Http\Controllers\Api\SystemController::class, 'getSettings']);

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Profile
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::post('/profile/avatar', [ProfileController::class, 'uploadAvatar']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead']);
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::delete('/notifications/{notification}', [NotificationController::class, 'destroy']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Papers - Author & above
    Route::get('/papers/export', [PaperController::class, 'exportCsv'])
        ->middleware('role:admin,super_admin');
    Route::get('/papers', [PaperController::class, 'index']);
    Route::get('/papers/{paper}', [PaperController::class, 'show']);
    Route::get('/papers/{paper}/download', [PaperController::class, 'download']);
    Route::get('/papers/{paper}/download-word', [PaperController::class, 'downloadWord']);
    Route::post('/papers', [PaperController::class, 'store'])
        ->middleware('role:author,admin,super_admin');
    Route::post('/papers/{paper}', [PaperController::class, 'update'])
        ->middleware('role:author,admin,super_admin');
    Route::delete('/papers/{paper}', [PaperController::class, 'destroy'])
        ->middleware('role:admin,super_admin');

    // Assign reviewer (Admin only)
    Route::post('/papers/{paper}/assign-reviewer', [PaperController::class, 'assignReviewer'])
        ->middleware('role:admin,super_admin');

    // Reviews
    Route::get('/papers/{paper}/reviews', [ReviewController::class, 'index']);
    Route::get('/reviews/{review}/download', [ReviewController::class, 'download']);
    Route::post('/reviews', [ReviewController::class, 'store'])
        ->middleware('role:reviewer,admin,super_admin');
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])
        ->middleware('role:reviewer,admin,super_admin');
    Route::get('/my-review-queue', [ReviewController::class, 'myQueue'])
        ->middleware('role:reviewer');

    // User Management (Admin only)
    Route::middleware('role:admin,super_admin')->group(function () {
        Route::get('/users/export', [UserController::class, 'exportCsv']);
        Route::get('/users/template', [UserController::class, 'downloadCsvTemplate']);
        Route::post('/users/import', [UserController::class, 'importCsv']);
        
        Route::get('/users', [UserController::class, 'index']);
        Route::post('/users', [UserController::class, 'store']);
        Route::get('/users/{user}', [UserController::class, 'show']);
        Route::put('/users/{user}', [UserController::class, 'update']);
        Route::delete('/users/{user}', [UserController::class, 'destroy']);
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive']);
    });

    // Get available reviewers (Admin only)
    Route::get('/reviewers', [UserController::class, 'reviewers'])
        ->middleware('role:admin,super_admin');

    // System Management (Super Admin only)
    Route::post('/settings', [\App\Http\Controllers\Api\SystemController::class, 'updateSettings'])
        ->middleware('role:super_admin');
    Route::get('/system/backup', [\App\Http\Controllers\Api\SystemController::class, 'downloadBackup'])
        ->middleware('role:super_admin');
});
