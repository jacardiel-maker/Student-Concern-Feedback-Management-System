<?php

use App\Http\Controllers\Admin\AssignmentController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\DatabaseBackupController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UnmaskController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\Auth\GoogleLoginController;
use App\Http\Controllers\Auth\GoogleOnboardingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ConcernController;
use App\Http\Controllers\ConcernPrintController;
use App\Http\Controllers\ConcernShowController;
use App\Http\Controllers\ConcernSuggestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\SessionKeepAliveController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\Teacher\AssignedConcernController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : view('welcome');
})->name('home');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login.show');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:login')->name('login.store');
    Route::get('/register', [RegisterController::class, 'show'])->name('register.show');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');

    Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirect'])
        ->middleware('throttle:6,1')
        ->name('auth.google.redirect');
    Route::get('/auth/google/callback', [GoogleLoginController::class, 'callback'])
        ->middleware('throttle:6,1')
        ->name('auth.google.callback');
    Route::get('/auth/google/complete', [GoogleOnboardingController::class, 'show'])
        ->name('auth.google.complete');
    Route::post('/auth/google/complete', [GoogleOnboardingController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('auth.google.complete.store');
});

Route::post('/language', LanguageController::class)->name('language.switch');

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::post('/session/keep-alive', SessionKeepAliveController::class)->name('session.keep-alive');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}', [NotificationController::class, 'read'])
        ->whereNumber('notification')
        ->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    Route::get('/attachments/{attachment}', [AttachmentController::class, 'download'])
        ->whereNumber('attachment')
        ->name('attachments.download');

    Route::get('/concerns', [ConcernController::class, 'index'])->name('concerns.index');
    Route::get('/concerns/create', [ConcernController::class, 'create'])->name('concerns.create');
    Route::get('/concerns/suggestions', ConcernSuggestionController::class)->name('concerns.suggestions');
    Route::post('/concerns', [ConcernController::class, 'store'])->name('concerns.store');
    Route::get('/concerns/{concern}/print', [ConcernPrintController::class, 'show'])
        ->whereNumber('concern')
        ->name('concerns.print');
    Route::get('/concerns/{concern}', [ConcernShowController::class, 'show'])->whereNumber('concern')->name('concerns.show');
    Route::get('/concerns/{concern}/rate', [RatingController::class, 'create'])
        ->whereNumber('concern')
        ->name('concerns.rate.create');
    Route::patch('/concerns/{concern}/status', [StatusController::class, 'update'])
        ->middleware('role:teacher|admin')
        ->whereNumber('concern')
        ->name('concerns.status.update');
    Route::post('/concerns/{concern}/replies', [ReplyController::class, 'store'])
        ->whereNumber('concern')
        ->name('concerns.reply');
    Route::post('/concerns/{concern}/rating', [RatingController::class, 'store'])
        ->whereNumber('concern')
        ->name('concerns.rate');

    Route::prefix('teacher')->name('teacher.')->middleware('role:teacher')->group(function (): void {
        Route::get('/assigned', [AssignedConcernController::class, 'index'])->name('assigned.index');
        Route::get('/assigned/{concern}', [ConcernShowController::class, 'show'])
            ->whereNumber('concern')
            ->name('assigned.show');
    });

    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function (): void {
        Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
        Route::get('/users/trashed', [UserManagementController::class, 'trashed'])->name('users.trashed');
        Route::get('/users/create', [UserManagementController::class, 'create'])->name('users.create');
        Route::post('/users', [UserManagementController::class, 'store'])->name('users.store');
        Route::get('/users/{user}/edit', [UserManagementController::class, 'edit'])
            ->whereNumber('user')
            ->name('users.edit');
        Route::put('/users/{user}', [UserManagementController::class, 'update'])
            ->whereNumber('user')
            ->name('users.update');
        Route::delete('/users/{user}', [UserManagementController::class, 'destroy'])
            ->whereNumber('user')
            ->name('users.destroy');
        Route::post('/users/{user}/restore', [UserManagementController::class, 'restore'])
            ->whereNumber('user')
            ->name('users.restore');
        Route::post('/users/{user}/approve', [UserManagementController::class, 'approve'])
            ->whereNumber('user')
            ->name('users.approve');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])
            ->whereNumber('category')
            ->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])
            ->whereNumber('category')
            ->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->whereNumber('category')
            ->name('categories.destroy');

        Route::get('/assignments', [AssignmentController::class, 'index'])->name('assignments.index');
        Route::post('/assignments/{concern}', [AssignmentController::class, 'store'])
            ->whereNumber('concern')
            ->name('assignments.store');
        Route::delete('/assignments/{concern}', [AssignmentController::class, 'destroy'])
            ->whereNumber('concern')
            ->name('assignments.destroy');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export.csv', [ReportController::class, 'csv'])->name('reports.csv');
        Route::get('/reports/print', [ReportController::class, 'print'])->name('reports.print');

        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/backup/download', [DatabaseBackupController::class, 'download'])->name('backup.download');

        Route::get('/unmask', [UnmaskController::class, 'index'])->name('unmask.index');
        Route::get('/unmask/{concern}', [UnmaskController::class, 'show'])
            ->whereNumber('concern')
            ->name('unmask.show');
        Route::post('/unmask/{concern}', [UnmaskController::class, 'store'])
            ->whereNumber('concern')
            ->name('unmask.store');
    });
});
