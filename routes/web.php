<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SavingController;
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', function () {
    return Inertia::render('LandingPage', [
        'title' => 'Landing Page',
    ]);
});

// Authentication Routes
Route::prefix('auth')
    ->name('auth.')
    ->middleware('guest')
    ->group(function () {

        Route::get('/register', [RegisterController::class, 'create'])
            ->name('register');

        Route::post('/register', [RegisterController::class, 'store'])
            ->name('register.store');

        Route::get('/register/success', function () {
            return Inertia::render('Auth/RegisterSuccess');
        })->name('register.success');
    });

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/admins', [AdminController::class, 'list']);
    Route::get('/create', [AdminController::class, 'create']);
    Route::get('/show/{id}', [AdminController::class, 'store']);
    Route::get('/verifikasi', [UserController::class, 'prospectiveMembers'])->name('users.prospective');

});
