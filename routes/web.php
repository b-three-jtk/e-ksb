<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SavingController;

Route::get('/', function () {
    return Inertia::render('LandingPage', [
        'title' => 'Landing Page',
    ]);
});


Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return Inertia::render('Admin/Dashboard', [
            'title' => 'Dashboard',
        ]);
    });
    Route::get('/savings/show/{id}', [SavingController::class, 'show']);
    Route::get('/users/show/{id}', [UserController::class, 'show']);

    Route::get('/create', [AdminController::class, 'create']);
    Route::get('/show/{id}', [AdminController::class, 'show']);
});
