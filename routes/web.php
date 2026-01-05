<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SavingController;
use App\Http\Controllers\Admin\DashboardController;

Route::get('/', function () {
    return Inertia::render('LandingPage', [
        'title' => 'Landing Page',
    ]);
});

Route::get('/auth/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/savings/show/{id}', [SavingController::class, 'show'])->name('savings.show');
    Route::put('/savings/validate/{id}', [SavingController::class, 'validateRequest'])->name('savings.validate');

    Route::get('/users/show/{id}', [UserController::class, 'show'])->name('users.show');

    Route::get('/create', [AdminController::class, 'create'])->name('create');
    Route::post('/store', [AdminController::class, 'store'])->name('store');
    Route::get('/show/{id}', [AdminController::class, 'show'])->name('show');
    Route::get('/edit/{id}', [AdminController::class, 'edit'])->name('edit');
    Route::put('/{id}', [AdminController::class, 'update'])->name('update');
});
