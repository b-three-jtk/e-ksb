<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinancingController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ResignationController;
use App\Http\Controllers\Admin\SavingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\AnggotaController;
use App\Http\Controllers\User\LedgerController;
use App\Http\Controllers\User\SimpananController;
use App\Http\Controllers\User\UserFinancingController;
use App\Http\Controllers\User\UserProfileController;
use App\Http\Controllers\User\UserRepaymentController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('LandingPage', [
        'title' => 'Landing Page',
    ]);
})->name('landing');

Route::get('/products', function () {
    return Inertia::render('Product');
})->name('products');

Route::get('/about', function () {
    return Inertia::render('About');
})->name('about');

Route::get('/faq', function () {
    return Inertia::render('Faq');
})->name('faq');

// Authentication Routes
Route::prefix('auth')
    ->name('auth.')
    ->middleware('guest')
    ->group(function () {

        Route::get('/login', [LoginController::class, 'create'])
            ->name('login');

        Route::post('/login', [LoginController::class, 'store'])
            ->name('login.store');

        Route::get('/forgot-password', [ForgotPasswordController::class, 'index'])
            ->name('password.request');

        Route::post('/forgot-password', [ForgotPasswordController::class, 'submitRequest'])
            ->name('password.email');

        Route::get('/reset-password/{token}', [ResetPasswordController::class, 'index'])
            ->name('password.reset');

        Route::post('/reset-password', [ResetPasswordController::class, 'submitRequest'])
            ->name('password.update');

    });

Route::redirect('/login', '/auth/login')->middleware('guest')->name('login');

Route::post('/auth/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('auth.logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin', 'revalidate'])->group(function () {
    Route::get('/users/verification', [UserController::class, 'prospectiveMembers'])
        ->name('users.prospective');

    Route::post('/verifikasi/{user:member_number}/approval', [UserController::class, 'submitApproval'])
        ->name('users.verification.submit');

    Route::get('/list', [AdminController::class, 'index'])->name('index');
    Route::get('/create', [AdminController::class, 'create']);
    Route::post('/store', [AdminController::class, 'store']);
    Route::get('/edit/{id}', [AdminController::class, 'edit']);
    Route::put('/update/{id}', [AdminController::class, 'update'])->name('update');
    Route::get('/show/{id}', [AdminController::class, 'show']);

    Route::get('/savings/show/{id}', [SavingController::class, 'show'])->name('savings.show');
    Route::put('/savings/validate/{id}', [SavingController::class, 'validateRequest'])->name('savings.validate');
    Route::get('/savings/list', [SavingController::class, 'index'])->name('savings.index');
    Route::get('/savings/export/csv', [SavingController::class, 'exportCsv'])->name('savings.export.csv');
    Route::get('/savings/export/pdf', [SavingController::class, 'exportPdf'])->name('savings.export.pdf');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/users/show/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/accounts/{id}/mutasi', [UserController::class, 'getMutasi']);
    Route::get('/financings/{id}/riwayat', [UserController::class, 'getRiwayat']);
    Route::get('/users/list', [UserController::class, 'index'])->name('users.index');
    Route::put('/users/{id}/nonactive', [UserController::class, 'updateStatusToInactive'])->name('users.nonactive');

    // Resignation Routes
    Route::get('/resignations/list', [ResignationController::class, 'index'])->name('resignations.index');
    Route::get('/resignation/{id}', [ResignationController::class, 'validation'])->name('resignations.validation');
    Route::put('/resignation/{id}', [ResignationController::class, 'validate'])->name('resignations.validate');

    Route::get('/financing/show/{id}', [FinancingController::class, 'show'])->name('financing.show');

    Route::get('/simpanan/penyetoran', [SimpananController::class, 'createDeposit'])->name('deposit.create');
    Route::post('/simpanan/penyetoran', [SimpananController::class, 'storeDeposit'])->name('deposit.store');
});

// User Routes
Route::prefix('user')->name('user.')->middleware(['auth', 'role:user', 'revalidate'])->group(function () {
    Route::get('/dashboard', [AnggotaController::class, 'index'])->name('userDashboard');

    Route::get('/profile', [UserProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/picture', [UserProfileController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/picture', [UserProfileController::class, 'deleteProfilePicture'])->name('profile.picture.delete');

    Route::get('/resign', [AnggotaController::class, 'createResign'])->name('resign.create');
    Route::post('/resign', [AnggotaController::class, 'storeResign'])->name('resign.store');

    // Ledger Routes
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');

    // Simpanan Routes - Penarikan
    Route::get('/simpanan/penarikan', [SimpananController::class, 'showWithdrawalInfo'])->name('simpanan.withdraw.info');
    Route::get('/simpanan/penarikan/detail', [SimpananController::class, 'showWithdrawalDetail'])->name('simpanan.withdraw.detail');
    Route::post('/simpanan/penarikan/pernyataan', [SimpananController::class, 'showWithdrawalStatement'])->name('simpanan.withdraw.statement');
    Route::post('/simpanan/penarikan/submit', [SimpananController::class, 'submitWithdrawal'])->name('simpanan.withdraw.submit');

    // Pembiayaan
    Route::get('/financing/repayment/show/{id}', [UserRepaymentController::class, 'show'])->name('financing.repayment.show');
    Route::post('/financing/repayment/submit', [UserRepaymentController::class, 'sendRequest'])->name('financing.repayment.request');
    Route::get('/financing/show/{id}', [UserFinancingController::class, 'show'])->name('financing.show');
});
