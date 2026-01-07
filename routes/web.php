<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
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

        Route::get('/login', [LoginController::class, 'create'])
            ->name('login');

        Route::post('/login', [LoginController::class, 'store'])
            ->name('login.store');

        Route::get('/register', [RegisterController::class, 'create'])
            ->name('register');

        Route::post('/register', [RegisterController::class, 'store'])
            ->name('register.store');

        Route::get('/register/success', function () {
            return Inertia::render('Auth/RegisterSuccess');
        })->name('register.success');
    });

Route::redirect('/login', '/auth/login')->middleware('guest');

Route::post('/auth/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.logout');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/savings/show/{id}', [SavingController::class, 'show'])->name('savings.show');
    Route::put('/savings/validate/{id}', [SavingController::class, 'validateRequest'])->name('savings.validate');

    Route::get('/users/show/{user:member_number}', [UserController::class, 'show'])->name('users.show');

    Route::get('/create', [AdminController::class, 'create']);
    Route::post('/store', [AdminController::class, 'store']);
    Route::get('/show/{id}', [AdminController::class, 'show']);
    Route::get('/verifikasi', [UserController::class, 'prospectiveMembers'])
        ->name('users.prospective');
    
    Route::get('/verifikasi/{user:member_number}', [UserController::class, 'verificationDetail'])
        ->name('users.verification.show');
    
    Route::post('/verifikasi/{user:member_number}/approval', [UserController::class, 'submitApproval'])
        ->name('users.verification.submit');
});

// User Routes
Route::prefix('user')->name('user.')->group(function () {
    Route::get('/profile/{user:member_number}', [UserController::class, 'profile'])->name('profile.show');
    Route::get('/profile/{user:member_number}/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/{user:member_number}', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/{user:member_number}/picture', [UserController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/{user:member_number}/picture', [UserController::class, 'deleteProfilePicture'])->name('profile.picture.delete');
});
