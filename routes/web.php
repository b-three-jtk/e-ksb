<?php

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinancingController;
use App\Http\Controllers\Admin\ProductTypeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ResignationController;
use App\Http\Controllers\Admin\SavingController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\LedgerController;
use App\Http\Controllers\User\MemberController;
use App\Http\Controllers\User\UserController as UserUserController;
use App\Http\Controllers\User\UserFinancingController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$adminRoles = [
    UserRoleEnum::KETUA->value,
    UserRoleEnum::SEKRETARIS->value,
    UserRoleEnum::PENGAWAS->value,
    UserRoleEnum::BENDAHARA->value,
    UserRoleEnum::DPS->value,
    UserRoleEnum::KETUAMURABAHAH->value,
    UserRoleEnum::STAFMURABAHAH->value,
    UserRoleEnum::PJANGGOTA->value
];

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

        Route::get('/login', [LoginController::class, 'loginPage'])
            ->name('login');

        Route::post('/login', [LoginController::class, 'login'])
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

Route::post('/auth/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:' . implode('|', $adminRoles), 'revalidate'])->group(function () {
    //  Pengelolaan Anggota
    Route::get('/users/list', [AdminUserController::class, 'index'])->middleware('permission:view_anggota')->name('users.index');
    Route::get('/users/create', [AdminUserController::class, 'create'])->middleware('permission:create_anggota')->name('users.create');
    Route::post('/users/store', [AdminUserController::class, 'store'])->middleware('permission:create_anggota')->name('users.store');
    Route::get('/users/show/{id}', [AdminUserController::class, 'show'])->middleware('permission:view_anggota')->name('users.show');
    Route::put('/users/{id}/disable', [AdminUserController::class, 'updateStatusToInactive'])->middleware('permission:edit_anggota')->name('users.disable');
    Route::get('/accounts/{id}/mutasi', [AdminUserController::class, 'getMutasi'])->middleware('permission:view_anggota')->name('users.mutasi');
    Route::get('/financings/{id}/history', [AdminUserController::class, 'getRiwayat'])->middleware('permission:view_anggota')->name('users.financing_history');

    // Pengelolaan Pengurus
    Route::get('/list', [AdminController::class, 'index'])->middleware('permission:view_pengurus')->name('admin.index');
    Route::get('/create', [AdminController::class, 'create'])->middleware('permission:create_pengurus')->name('admin.create');
    Route::post('/store', [AdminController::class, 'store'])->middleware('permission:create_pengurus')->name('admin.store');
    Route::get('/edit/{id}', [AdminController::class, 'edit'])->middleware('permission:edit_pengurus')->name('admin.edit');
    Route::put('/update/{id}', [AdminController::class, 'update'])->middleware('permission:edit_pengurus')->name('admin.update');
    Route::get('/show/{id}', [AdminController::class, 'show'])->middleware('permission:view_pengurus')->name('admin.show');
    Route::get('members', [AdminController::class, 'searchMember'])->middleware('permission:view_anggota')->name('members.search');

    // Pengelolaan Pengunduran Diri
    Route::get('/resignations/list', [ResignationController::class, 'index'])->middleware('permission:view_pengunduran_diri')->name('resignations.index');
    Route::get('/resignations/{id}', [ResignationController::class, 'validation'])->middleware('permission:edit_pengunduran_diri')->name('resignations.validation');
    Route::put('/resignations/{id}', [ResignationController::class, 'validate'])->middleware('permission:edit_pengunduran_diri')->name('resignations.validate');

    // Pengelolaan Simpanan
    Route::get('/savings/list', [SavingController::class, 'index'])->middleware('permission:view_simpanan')->name('savings.index');
    Route::get('/savings/withdrawal', [WithdrawalController::class, 'create'])->middleware('permission:create_penarikan')->name('savings.withdrawal.create');
    Route::post('/savings/withdrawal', [WithdrawalController::class, 'store'])->middleware('permission:create_penarikan')->name('savings.withdrawal.store');
    Route::get('/savings/deposit', [SavingController::class, 'createDeposit'])->middleware('permission:create_simpanan')->name('savings.deposit.create');
    Route::post('/savings/deposit', [SavingController::class, 'storeDeposit'])->middleware('permission:create_simpanan')->name('savings.deposit.store');
    Route::get('/savings/show/{id}', [SavingController::class, 'show'])->middleware('permission:view_simpanan')->name('savings.show');
    Route::get('/savings/export/csv', [SavingController::class, 'exportCsv'])->middleware('permission:view_simpanan')->name('savings.export.csv');
    Route::get('/savings/export/pdf', [SavingController::class, 'exportPdf'])->middleware('permission:view_simpanan')->name('savings.export.pdf');

    // Pengelolaan Pembiayaan Murabahah
    Route::get('/financings', [FinancingController::class, 'index'])->middleware('permission:view_murabahah')->name('financings.index');
    Route::get('/financings/show/{id}', [FinancingController::class, 'show'])->middleware('permission:view_murabahah')->name('financings.show');
    Route::get('/financings/create', [FinancingController::class, 'create'])->middleware('permission:create_murabahah')->name('financings.create');
    Route::get('/members/search', [FinancingController::class, 'searchMembers'])->middleware('permission:create_murabahah')->name('members.search');
    Route::get('/suppliers/search', [FinancingController::class, 'searchSuppliers'])->middleware('permission:create_murabahah')->name('suppliers.search');
    Route::post('/financings/store', [FinancingController::class, 'store'])->middleware('permission:create_murabahah')->name('financings.store');
    Route::resource('product-types', ProductTypeController::class)->middleware('permission:create_murabahah');
    Route::get('/financings/draft/{id}', [FinancingController::class, 'loadDraft'])->middleware('permission:create_murabahah')->name('financings.load-draft');
    Route::get('/financings/validation/{id}', [FinancingController::class, 'showValidation'])->middleware('permission:approve_murabahah')->name('financings.validation');
    Route::put('/financings/validate/{id}', [FinancingController::class, 'validate'])->middleware('permission:approve_murabahah')->name('financings.validation.submit');
    Route::get('/financings/repayment/{id}', [FinancingController::class, 'showRepayment'])->middleware('permission:edit_murabahah')->name('financings.repayment');
    Route::post('/financings/repayment', [FinancingController::class, 'storeRepayment'])->middleware('permission:edit_murabahah')->name('financings.repayment.request');
    Route::get('repayment/{id}/receipt', [FinancingController::class, 'viewRepaymentReceipt'])->middleware('permission:edit_murabahah')->name('financings.repayment.view');
    Route::get('repayment/{id}/download', [FinancingController::class, 'downloadRepaymentReceipt'])->middleware('permission:edit_murabahah')->name('financings.repayment.download');

    // Pengelolaan Kas
    // Pengaturan Umum

    // Personal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
});

// User Routes
Route::prefix('user')->name('user.')->middleware(['auth', 'role:Anggota', 'revalidate'])->group(function () {
    Route::get('/dashboard', [MemberController::class, 'index'])->name('userDashboard');

    Route::get('/profile', [UserUserController::class, 'profileShow'])->name('profile.show');
    Route::get('/profile/edit', [UserUserController::class, 'profileEdit'])->name('profile.edit');
    Route::put('/profile', [UserUserController::class, 'profileUpdate'])->name('profile.update');
    Route::post('/profile/picture', [UserUserController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/picture', [UserUserController::class, 'deleteProfilePicture'])->name('profile.picture.delete');
    Route::post('/profile/update-password', [UserUserController::class, 'updatePassword'])->name('profile.update-password');

    Route::get('/resign', [MemberController::class, 'createResign'])->name('resign.create');
    Route::post('/resign', [MemberController::class, 'storeResign'])->name('resign.store');

    // Ledger
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');

    // Pembiayaan
    Route::get('/financings', [UserFinancingController::class, 'index'])->name('financing.index');
    Route::get('/financings/show/{id}', [UserFinancingController::class, 'show'])->name('financing.show');
});
