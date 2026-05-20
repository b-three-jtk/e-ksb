<?php

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinancingController;
use App\Http\Controllers\Admin\ProductTypeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ResignationController;
use App\Http\Controllers\Admin\SavingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\WithdrawalController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\LedgerController;
use App\Http\Controllers\User\MemberController;
use App\Http\Controllers\User\UserFinancingController;
use App\Http\Controllers\User\UserProfileController;
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
    Route::get('/list', [AdminController::class, 'index'])->name('index')->middleware('role:Sekretaris|Ketua');
    Route::get('/create', [AdminController::class, 'create'])->middleware('role:Sekretaris');
    Route::post('/store', [AdminController::class, 'store'])->middleware('role:Sekretaris');
    Route::get('/edit/{id}', [AdminController::class, 'edit'])->middleware('role:Sekretaris');;
    Route::put('/update/{id}', [AdminController::class, 'update'])->name('update')->middleware('role:Sekretaris');
    Route::get('/show/{id}', [AdminController::class, 'show']);
    Route::get('members', [AdminController::class, 'searchMember'])->name('members.search');

    Route::get('/savings/show/{id}', [SavingController::class, 'show'])->name('savings.show');
    Route::put('/savings/validate/{id}', [SavingController::class, 'validateRequest'])->name('savings.validate');
    Route::get('/savings/list', [SavingController::class, 'index'])->name('savings.index')->middleware('role:Sekretaris|Ketua');
    Route::get('/savings/export/csv', [SavingController::class, 'exportCsv'])->name('savings.export.csv');
    Route::get('/savings/export/pdf', [SavingController::class, 'exportPdf'])->name('savings.export.pdf');

    Route::get('/saving/withdrawal', [WithdrawalController::class, 'create'])->name('withdrawal.create')->middleware('role:Penanggung Jawab Anggota');
    Route::post('/saving/withdrawal', [WithdrawalController::class, 'store'])->name('withdrawal.store')->middleware('role:Penanggung Jawab Anggota');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/users/show/{id}', [UserController::class, 'show'])->name('users.show');
    Route::get('/accounts/{id}/mutasi', [UserController::class, 'getMutasi']);
    Route::get('/financings/{id}/riwayat', [UserController::class, 'getRiwayat']);
    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('role:Sekretaris')
        ->name('users.create');
    Route::post('/users/store', [UserController::class, 'store'])
        ->middleware('role:Sekretaris')
        ->name('users.store');
    Route::get('/users/list', [UserController::class, 'index'])->name('users.index')->middleware('role:Sekretaris|Ketua');
    Route::put('/users/{id}/nonactive', [UserController::class, 'updateStatusToInactive'])->name('users.nonactive');

    // Resignation Routes
    Route::get('/resignations/list', [ResignationController::class, 'index'])->name('resignations.index');
    Route::get('/resignation/{id}', [ResignationController::class, 'validation'])->name('resignations.validation')->middleware('role:Ketua');
    Route::put('/resignation/{id}', [ResignationController::class, 'validate'])->name('resignations.validate')->middleware('role:Ketua');

    // financing routes
    Route::get('/financing', [FinancingController::class, 'index'])->name('financing.index');
    Route::get('/financing/show/{id}', [FinancingController::class, 'show'])->name('financing.show');
    Route::get('/financing/create', [FinancingController::class, 'create'])->name('financing.create');
    Route::get('/members/search', [FinancingController::class, 'searchMembers'])->name('members.search');
    Route::get('/suppliers/search', [FinancingController::class, 'searchSuppliers'])->name('suppliers.search');
    Route::post('/financing/store', [FinancingController::class, 'store'])->name('financing.store');
    Route::resource('product-types', ProductTypeController::class);
    Route::get('/financing/draft/{id}', [FinancingController::class, 'loadDraft'])->name('financing.load-draft');
    Route::get('/financing/validation/{id}', [FinancingController::class, 'showValidation'])->name('financing.validation');
    Route::put('/financing/validate/{id}', [FinancingController::class, 'validate'])->name('financing.validation.submit');
    Route::get('/financing/repayment/{id}', [FinancingController::class, 'showRepayment'])->name('financing.repayment');
    Route::post('/financing/repayment', [FinancingController::class, 'storeRepayment'])->name('financing.repayment.request');
    Route::get('repayment/{id}/receipt', [FinancingController::class, 'viewRepaymentReceipt'])->name('financing.repayment.view');
    Route::get('repayment/{id}/download', [FinancingController::class, 'downloadRepaymentReceipt'])->name('financing.repayment.download');

    Route::get('/saving/deposit', [SavingController::class, 'createDeposit'])->name('deposit.create')->middleware('role:Penanggung Jawab Anggota');
    Route::post('/saving/deposit', [SavingController::class, 'storeDeposit'])->name('deposit.store')->middleware('role:Penanggung Jawab Anggota');
});

// User Routes
Route::prefix('user')->name('user.')->middleware(['auth', 'role:Anggota', 'revalidate'])->group(function () {
    Route::get('/dashboard', [MemberController::class, 'index'])->name('userDashboard');

    Route::get('/profile', [UserProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [UserProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [UserProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/picture', [UserProfileController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/picture', [UserProfileController::class, 'deleteProfilePicture'])->name('profile.picture.delete');
    Route::post('/profile/update-password', [UserProfileController::class, 'updatePassword'])->name('profile.update-password');

    Route::get('/resign', [MemberController::class, 'createResign'])->name('resign.create');
    Route::post('/resign', [MemberController::class, 'storeResign'])->name('resign.store');

    // Ledger Routes
    Route::get('/ledger', [LedgerController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/export', [LedgerController::class, 'export'])->name('ledger.export');

    // Pembiayaan
    Route::get('/financing', [UserFinancingController::class, 'index'])->name('financing.index');
    Route::get('/financing/show/{id}', [UserFinancingController::class, 'show'])->name('financing.show');
});
