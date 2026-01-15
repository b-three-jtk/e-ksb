<?php

use App\Http\Controllers\Admin\ProfileController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\SavingController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\User\LedgerController;
use App\Http\Controllers\User\AnggotaController;
use App\Http\Controllers\User\SimpananController;
use App\Models\User;

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

Route::redirect('/login', '/auth/login')->middleware('guest')->name('login');

Route::post('/auth/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.logout');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');

    Route::get('/savings/show/{id}', [SavingController::class, 'show'])->name('savings.show');
    Route::put('/savings/validate/{id}', [SavingController::class, 'validateRequest'])->name('savings.validate');
    Route::get('/savings/list', [SavingController::class, 'index'])->name('savings.index');
    Route::get('/savings/export/csv', [SavingController::class, 'exportCsv'])->name('savings.export.csv');
    Route::get('/savings/export/pdf', [SavingController::class, 'exportPdf'])->name('savings.export.pdf');

    Route::get('/users/show/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/users/list', [UserController::class, 'index'])->name('users.index');

    Route::get('/list', [AdminController::class, 'index'])->name('admin.admins.index');
    Route::get('/create', [AdminController::class, 'create']);
    Route::post('/store', [AdminController::class, 'store']);
    Route::get('/edit/{id}', [AdminController::class, 'edit']);
    Route::put('/update/{id}', [AdminController::class, 'update'])->name('update');
    Route::get('/show/{id}', [AdminController::class, 'show']);

    Route::get('/users/verification', [UserController::class, 'prospectiveMembers'])
        ->name('users.prospective');

    Route::get('/verifikasi/{user:member_number}', [UserController::class, 'verificationDetail'])
        ->name('users.verification.show');

    Route::post('/verifikasi/{user:member_number}/approval', [UserController::class, 'submitApproval'])
        ->name('users.verification.submit');
});

// User Routes
Route::prefix('user')->name('user.')->middleware('auth')->group(function () {
    Route::get('/dashboard', [AnggotaController::class, 'index'])->name('userDashboard');

    Route::get('/profile/{user:member_number}', [UserController::class, 'profile'])->name('profile.show');
    Route::get('/profile/{user:member_number}/edit', [UserController::class, 'editProfile'])->name('profile.edit');
    Route::put('/profile/{user:member_number}', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::post('/profile/{user:member_number}/picture', [UserController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/{user:member_number}/picture', [UserController::class, 'deleteProfilePicture'])->name('profile.picture.delete');

    Route::get('/resign', [AnggotaController::class, 'createResign'])->name('resign.create');
    Route::post('/resign', [AnggotaController::class, 'storeResign'])->name('resign.store');

    Route::get('/simpanan/penyetoran', [SimpananController::class, 'createDeposit'])->name('deposit.create');
    Route::post('/simpanan/penyetoran', [SimpananController::class, 'storeDeposit'])->name('deposit.store');
    
    // Ledger Routes
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');

    // Simpanan Routes - Penarikan
    Route::get('/simpanan/penarikan', [SimpananController::class, 'showWithdrawalInfo'])->name('simpanan.withdraw.info');
    Route::get('/simpanan/penarikan/detail', [SimpananController::class, 'showWithdrawalDetail'])->name('simpanan.withdraw.detail');
    Route::get('/simpanan/penarikan/pernyataan', [SimpananController::class, 'showWithdrawalStatement'])->name('simpanan.withdraw.statement');
    Route::post('/simpanan/penarikan/submit', [SimpananController::class, 'submitWithdrawal'])->name('simpanan.withdraw.submit');
});
