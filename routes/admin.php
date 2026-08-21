<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\SupportTicketController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::post('/users/{user}/suspend', [UserController::class, 'suspend'])->name('users.suspend');
    Route::post('/users/{user}/credit', [UserController::class, 'credit'])->name('users.credit');

    Route::get('/settings/margin', [SettingsController::class, 'margin'])->name('settings.margin');
    Route::post('/settings/margin', [SettingsController::class, 'updateMargin'])->name('settings.margin.update');

    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions');

    Route::get('/support', [SupportTicketController::class, 'index'])->name('support');
    Route::post('/support/{ticket}/status', [SupportTicketController::class, 'updateStatus'])->name('support.status');
});
