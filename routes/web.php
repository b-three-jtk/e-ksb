<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;

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
