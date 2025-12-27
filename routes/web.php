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
    Route::post('/store', [AdminController::class, 'store']);
    Route::get('/show/{id}', [AdminController::class, 'show']);
    Route::get('/verifikasi', [UserController::class, 'prospectiveMembers'])->name('users.prospective');

    Route::get('/verifikasi', [UserController::class, 'prospectiveMembers'])
        ->name('users.prospective');
    
    Route::get('/verifikasi/{id}', [UserController::class, 'verificationDetail'])
        ->name('users.verification.show');
    
    Route::post('/verifikasi/{id}/approval', [UserController::class, 'submitApproval'])
        ->name('users.verification.submit');
});

// User Routes
Route::prefix('user')->name('user.')->group(function () {
    Route::get('/profile/{id}', [UserController::class, 'profile'])->name('profile.show');
    Route::get('/profile/{id}/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/{id}', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/{id}/picture', [UserController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/{id}/picture', [UserController::class, 'deleteProfilePicture'])->name('profile.picture.delete');
});
