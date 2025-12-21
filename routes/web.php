<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\SavingController;

Route::get('/', function () {
    return Inertia::render('LandingPage', [
        'title' => 'Landing Page',
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Admin/Dashboard', [
        'title' => 'Dashboard',
    ]);
});

Route::get('/savings/{id}', [SavingController::class, 'show'])->name('admin.savings.show');
Route::get('/users/{id}', [UserController::class, 'show'])->name('admin.users.show');
