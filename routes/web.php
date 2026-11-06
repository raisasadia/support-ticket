<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Modules\UserManagement\Controllers\UserController;
use App\Http\Middleware\EnsureUserIsActive;
use App\Modules\TicketManagement\Controllers\TicketController;
use App\Modules\TicketManagement\Controllers\DashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->middleware('verified')->name('dashboard');
    Route::get('/admin', [DashboardController::class, 'admin'])->name('admin.dashboard');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/deactivate', [ProfileController::class, 'deactivate'])->name('profile.deactivate');
});
Route::middleware(['auth', EnsureUserIsActive::class])->group(function () {
    Route::resource('tickets', TicketController::class)->only(['index', 'create', 'store', 'show', 'edit', 'update']);
    Route::post('/tickets/import', [TicketController::class, 'import'])->name('tickets.import');
    Route::get('/ticket-imports/{import}/status', [TicketController::class, 'importStatus'])->name('tickets.import-status');
    Route::post('/tickets/{ticket}/replies', [TicketController::class, 'reply'])->name('tickets.reply');
    Route::post('/tickets/{ticket}/attachments', [TicketController::class, 'attachment'])->name('tickets.attachments.store');
    Route::get('/ticket-attachments/{attachment}/download', [TicketController::class, 'download'])->name('tickets.attachments.download');
    Route::patch('/tickets/{ticket}/assign', [TicketController::class, 'assign'])->name('tickets.assign');
    Route::patch('/tickets/{ticket}/auto-assign', [TicketController::class, 'autoAssign'])->name('tickets.auto-assign');
    Route::patch('/tickets/{ticket}/claim', [TicketController::class, 'claim'])->name('tickets.claim');
    Route::patch('/tickets/{ticket}/status', [TicketController::class, 'status'])->name('tickets.status');

    Route::get('/users', [UserController::class, 'index'])
        ->name('users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->name('users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->name('users.store');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->name('users.edit');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->name('users.update');

    Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');
});

require __DIR__.'/auth.php';
