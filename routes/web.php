<?php

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FinancingController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\ProductTypeController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\ResignationController;
use App\Http\Controllers\Admin\SavingController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\AuthenticationController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\SavingController as UserSavingController;
use App\Http\Controllers\User\MemberController;
use App\Http\Controllers\User\FinancingController as UserFinancingController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\User\UserController as UserUserController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;
// use App\Http\Controllers\User\UserFinancingController;
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

        Route::get('/login', [AuthenticationController::class, 'loginPage'])
            ->name('login');

        Route::post('/login', [AuthenticationController::class, 'login'])
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

Route::post('/auth/logout', [AuthenticationController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:' . implode('|', $adminRoles), 'revalidate'])->group(function () {
    //  Pengelolaan Anggota
    Route::get('/users/list', [UserController::class, 'index'])->middleware('permission:view_anggota')->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->middleware('permission:create_anggota')->name('users.create');
    Route::post('/users/store', [UserController::class, 'store'])->middleware('permission:create_anggota')->name('users.store');
    Route::get('/users/show/{id}', [UserController::class, 'show'])->middleware('permission:view_anggota')->name('users.show');
    Route::get('/users/edit/{id}', [UserController::class, 'edit'])->middleware('permission:edit_anggota')->name('users.edit');
    Route::put('/users/{id}/update', [UserController::class, 'update'])->middleware('permission:edit_anggota')->name('users.update');
    Route::get('/users/allocation', [UserController::class, 'allocation'])->middleware('permission:edit_anggota')->name('users.allocation');
    Route::post('/users/allocation', [UserController::class, 'storeAllocation'])->middleware('permission:edit_anggota')->name('users.allocation.store');
    Route::put('/users/{id}/disable', [UserController::class, 'updateStatusToInactive'])->middleware('permission:edit_anggota')->name('users.disable');
    Route::get('/accounts/{id}/mutasi', [UserController::class, 'getMutasi'])->middleware('permission:view_anggota')->name('users.mutasi');
    Route::get('/financings/{id}/history', [UserController::class, 'getRiwayat'])->middleware('permission:view_anggota')->name('users.financing_history');

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
    Route::get('/savings/withdrawal', [SavingController::class, 'createWithdrawal'])->middleware('permission:create_simpanan')->name('savings.withdrawal.create');
    Route::post('/savings/withdrawal', [SavingController::class, 'storeWithdrawal'])->middleware('permission:create_simpanan')->name('savings.withdrawal.store');
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
    Route::post('/financings/draft', [FinancingController::class, 'saveDraft'])->middleware('permission:create_murabahah')->name('financings.draft');
    Route::post('/financings/finalize', [FinancingController::class, 'finalize'])->middleware('permission:create_murabahah')->name('financings.finalize');
    Route::post('/financings/store', [FinancingController::class, 'store'])->middleware('permission:create_murabahah')->name('financings.store');
    Route::resource('product-types', ProductTypeController::class)->middleware('permission:create_murabahah');
    Route::get('/financings/draft/{id}', [FinancingController::class, 'loadDraft'])->middleware('permission:create_murabahah')->name('financings.load-draft');
    Route::get('/financings/validation/{id}', [FinancingController::class, 'showValidation'])->middleware('permission:approve_murabahah')->name('financings.validation');
    Route::put('/financings/validate/{id}', [FinancingController::class, 'validate'])->middleware('permission:approve_murabahah')->name('financings.validation.submit');
    Route::get('/financings/repayment/{id}', [FinancingController::class, 'showRepayment'])->middleware('permission:edit_murabahah')->name('financings.repayment');
    Route::post('/financings/repayment', [FinancingController::class, 'storeRepayment'])->middleware('permission:edit_murabahah')->name('financings.repayment.request');
    Route::get('repayment/{id}/receipt', [FinancingController::class, 'viewRepaymentReceipt'])->middleware('permission:edit_murabahah')->name('financings.repayment.view');
    Route::get('repayment/{id}/download', [FinancingController::class, 'downloadRepaymentReceipt'])->middleware('permission:edit_murabahah')->name('financings.repayment.download');
    Route::get('/financings/{financing}/payments/create',[FinancingController::class, 'createPayment'])->middleware('permission:edit_murabahah')->name('financing.payments.create');
    Route::post('/financings/{financing}/payments/store', [FinancingController::class, 'storePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.store');
    Route::post('/financings/{financing}/payments/reschedule', [FinancingController::class, 'reschedulePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.reschedule');
    Route::get('/financings/{financing}/payments/create',[FinancingController::class, 'createPayment'])->middleware('permission:edit_murabahah')->name('financing.payments.create');
    Route::post('/financings/{financing}/payments/store', [FinancingController::class, 'storePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.store');
    Route::post('/financings/{financing}/payments/reschedule', [FinancingController::class, 'reschedulePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.reschedule');

    // Pengelolaan Kas
    Route::get('/accounts/list', [AccountController::class, 'index'])->middleware('permission:view_kas')->name('accounts.index');
    Route::post('/accounts/create', [AccountController::class, 'store'])->middleware('permission:create_kas')->name('accounts.create');
    Route::patch('/accounts/{id}/status', [AccountController::class, 'updateStatus'])->middleware('permission:edit_kas')->name('accounts.update-status');



    // Pengaturan Umum
    Route::middleware('role:' . UserRoleEnum::KETUA->value)->group(function () {
        Route::get('/settings', [SettingsController::class, 'index'])->middleware('permission:view_pengaturan')->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'store'])->middleware('permission:create_pengaturan')->name('settings.store');
    });

    // Personal
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');

    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index'])->middleware('permission:view_notifikasi')->name('notifications.index');

    // Peran dan Akses
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:view_peran_akses')->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->middleware('permission:create_peran_akses')->name('roles.create');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:create_peran_akses')->name('roles.store');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->middleware('permission:edit_peran_akses')->name('roles.edit');
    Route::put('/roles/{id}', [RoleController::class, 'update'])->middleware('permission:edit_peran_akses')->name('roles.update');
});

// User Routes
Route::prefix('user')->name('user.')->middleware(['auth', 'role:Anggota', 'revalidate'])->group(function () {
    Route::get('/dashboard', [MemberController::class, 'index'])->name('userDashboard');

    Route::get('/profile', [MemberController::class, 'profileShow'])->name('profile.show');
    Route::get('/profile/edit', [MemberController::class, 'profileEdit'])->name('profile.edit');
    Route::put('/profile', [MemberController::class, 'profileUpdate'])->name('profile.update');
    Route::post('/profile/picture', [MemberController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/picture', [MemberController::class, 'deleteProfilePicture'])->name('profile.picture.delete');
    Route::post('/profile/update-password', [MemberController::class, 'updatePassword'])->name('profile.update-password');

    Route::get('/resign', [MemberController::class, 'createResign'])->name('resign.create');
    Route::post('/resign', [MemberController::class, 'storeResign'])->name('resign.store');

    // Notifikasi
    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [UserNotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [UserNotificationController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::post('/notifications/mark-popup-displayed', [UserNotificationController::class, 'markPopupDisplayed'])->name('notifications.markPopupDisplayed');

    // Ledger
    Route::get('/ledger', [UserSavingController::class, 'index'])->name('ledger.index');
    Route::get('/ledger/export', [UserSavingController::class, 'export'])->name('ledger.export');

    // Pembiayaan
    Route::get('/financings', [UserFinancingController::class, 'index'])->name('financing.index');
    Route::get('/financings/show/{id}', [UserFinancingController::class, 'show'])->name('financing.show');
});
