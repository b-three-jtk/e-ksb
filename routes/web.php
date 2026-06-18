<?php

use App\Enums\UserRoleEnum;
use App\Http\Controllers\Admin\AkunController;
use App\Http\Controllers\Admin\PengurusController;
use App\Http\Controllers\Admin\AruskasController;
use App\Http\Controllers\Admin\DasborController;
use App\Http\Controllers\Admin\PembiayaanController;
use App\Http\Controllers\Admin\NotifikasiController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\PengunduranDiriController;
use App\Http\Controllers\Admin\PeranAksesController;
use App\Http\Controllers\Admin\SimpananController;
use App\Http\Controllers\PengaturanUmumController;
use App\Http\Controllers\Admin\PemasokController;
use App\Http\Controllers\Admin\PenggunaController;
use App\Http\Controllers\AutentikasiController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\User\PembiayaanController as UserPembiayaanController;
use App\Http\Controllers\User\AnggotaController;
use App\Http\Controllers\User\NotifikasiController as UserNotifikasiController;
use App\Http\Controllers\User\SimpananController as UserSimpananController;
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
    ->middleware('guest')
    ->group(function () {

        Route::get('/login', [AutentikasiController::class, 'loginPage'])
            ->name('login');

        Route::post('/login', [AutentikasiController::class, 'login'])
            ->name('login.store');

        Route::get('/forgot-password', [AutentikasiController::class, 'formForgotPassword'])
            ->name('password.request');

        Route::post('/forgot-password', [AutentikasiController::class, 'submitForgotPasswordRequest'])
            ->name('password.email');

        Route::get('/reset-password/{token}', [AutentikasiController::class, 'formResetPassword'])
            ->name('password.reset');

        Route::post('/reset-password', [AutentikasiController::class, 'submitResetPasswordRequest'])
            ->name('password.update');

    });

Route::redirect('/login', '/auth/login')->middleware('guest')->name('login');

Route::post('/auth/logout', [AutentikasiController::class, 'logout'])
    ->middleware('auth')
    ->name('auth.logout');

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:' . implode('|', $adminRoles), 'revalidate'])->group(function () {
    //  Pengelolaan Anggota
    Route::get('/users', [PenggunaController::class, 'index'])->middleware('permission:view_anggota')->name('users.index');
    Route::get('/users/create', [PenggunaController::class, 'create'])->middleware('permission:create_anggota')->name('users.create');
    Route::post('/users/store', [PenggunaController::class, 'store'])->middleware('permission:create_anggota')->name('users.store');
    Route::get('/users/show/{id}', [PenggunaController::class, 'show'])->middleware('permission:view_anggota')->name('users.show');
    Route::get('/users/edit/{id}', [PenggunaController::class, 'edit'])->middleware('permission:edit_anggota')->name('users.edit');
    Route::put('/users/{id}/update', [PenggunaController::class, 'update'])->middleware('permission:edit_anggota')->name('users.update');
    Route::get('/allocation', [PenggunaController::class, 'allocation'])->middleware('permission:edit_anggota')->name('allocation');
    Route::post('/allocation', [PenggunaController::class, 'storeAllocation'])->middleware('permission:edit_anggota')->name('allocation.store');
    Route::put('/users/{id}/disable', [PenggunaController::class, 'updateStatusToInactive'])->middleware('permission:edit_anggota')->name('users.disable');
    Route::get('/accounts/{id}/mutasi', [PenggunaController::class, 'getMutasi'])->middleware('permission:view_anggota')->name('users.mutasi');
    Route::get('/financings/{id}/history', [PenggunaController::class, 'getRiwayat'])->middleware('permission:view_anggota')->name('users.financing_history');

    // Pengelolaan Pengurus
    Route::get('/pengurus', [PengurusController::class, 'index'])->middleware('permission:view_pengurus')->name('admin.index');
    Route::get('/pengurus/create', [PengurusController::class, 'create'])->middleware('permission:create_pengurus')->name('admin.create');
    Route::post('/pengurus/store', [PengurusController::class, 'store'])->middleware('permission:create_pengurus')->name('admin.store');
    Route::get('/pengurus/edit/{id}', [PengurusController::class, 'edit'])->middleware('permission:edit_pengurus')->name('admin.edit');
    Route::put('/pengurus/update/{id}', [PengurusController::class, 'update'])->middleware('permission:edit_pengurus')->name('admin.update');
    Route::get('/pengurus/show/{id}', [PengurusController::class, 'show'])->middleware('permission:view_pengurus')->name('admin.show');
    Route::get('/pengurus/members', [PengurusController::class, 'searchMember'])->middleware('permission:view_anggota')->name('members.search');

    // Pengelolaan Pengunduran Diri
    Route::get('/resignations/list', [PengunduranDiriController::class, 'index'])->middleware('permission:view_pengunduran_diri')->name('resignations.index');
    Route::get('/resignations/{id}', [PengunduranDiriController::class, 'validation'])->middleware('permission:edit_pengunduran_diri')->name('resignations.validation');
    Route::put('/resignations/{id}', [PengunduranDiriController::class, 'validate'])->middleware('permission:edit_pengunduran_diri')->name('resignations.validate');

    // Pengelolaan Simpanan
    Route::get('/savings', [SimpananController::class, 'index'])->middleware('permission:view_simpanan')->name('savings.index');
    Route::get('/savings/withdrawal', [SimpananController::class, 'createWithdrawal'])->middleware('permission:create_simpanan')->name('savings.withdrawal.create');
    Route::post('/savings/withdrawal', [SimpananController::class, 'storeWithdrawal'])->middleware('permission:create_simpanan')->name('savings.withdrawal.store');
    Route::get('/savings/deposit', [SimpananController::class, 'createDeposit'])->middleware('permission:create_simpanan')->name('savings.deposit.create');
    Route::post('/savings/deposit', [SimpananController::class, 'storeDeposit'])->middleware('permission:create_simpanan')->name('savings.deposit.store');
    Route::get('/savings/show/{id}', [SimpananController::class, 'show'])->middleware('permission:view_simpanan')->name('savings.show');
    Route::get('/savings/export/excel', [SimpananController::class, 'exportExcel'])->middleware('permission:view_simpanan')->name('savings.export.excel');
    Route::get('/savings/export/pdf', [SimpananController::class, 'exportPdf'])->middleware('permission:view_simpanan')->name('savings.export.pdf');

    // Pengelolaan Pembiayaan Murabahah
    Route::get('/financings', [PembiayaanController::class, 'index'])->middleware('permission:view_murabahah')->name('financings.index');
    Route::get('/financings/show/{id}', [PembiayaanController::class, 'show'])->middleware('permission:view_murabahah')->name('financings.show');
    Route::get('/financings/create', [PembiayaanController::class, 'create'])->middleware('permission:create_murabahah')->name('financings.create');
    Route::get('/members/search', [PembiayaanController::class, 'searchMembers'])->middleware('permission:create_murabahah')->name('members.search');
    Route::get('/suppliers/search', [PembiayaanController::class, 'searchSuppliers'])->middleware('permission:create_murabahah')->name('suppliers.search');
    Route::post('/financings/draft', [PembiayaanController::class, 'saveDraft'])->middleware('permission:create_murabahah')->name('financings.draft');
    Route::post('/financings/finalize', [PembiayaanController::class, 'finalize'])->middleware('permission:create_murabahah')->name('financings.finalize');
    Route::post('/financings/store', [PembiayaanController::class, 'store'])->middleware('permission:create_murabahah')->name('financings.store');
    Route::post('/product-types', [PembiayaanController::class, 'storeProductType'])->middleware('permission:create_murabahah')->name('product-types.store');
    Route::post('/suppliers', [PembiayaanController::class, 'storeSupplier'])->middleware('permission:create_murabahah')->name('suppliers.store');
    Route::get('/financings/draft/{id}', [PembiayaanController::class, 'loadDraft'])->middleware('permission:create_murabahah')->name('financings.load-draft');
    Route::get('/financings/validation/{id}', [PembiayaanController::class, 'showValidation'])->middleware('permission:approve_murabahah')->name('financings.validation');
    Route::put('/financings/validate/{id}', [PembiayaanController::class, 'validate'])->middleware('permission:approve_murabahah')->name('financings.validation.submit');
    Route::get('/financings/repayment/{id}', [PembiayaanController::class, 'showRepayment'])->middleware('permission:edit_murabahah')->name('financings.repayment');
    Route::post('/financings/repayment', [PembiayaanController::class, 'storeRepayment'])->middleware('permission:edit_murabahah')->name('financings.repayment.request');
    Route::get('repayment/{id}/receipt', [PembiayaanController::class, 'viewRepaymentReceipt'])->middleware('permission:edit_murabahah')->name('financings.repayment.view');
    Route::get('repayment/{id}/download', [PembiayaanController::class, 'downloadRepaymentReceipt'])->middleware('permission:edit_murabahah')->name('financings.repayment.download');
    Route::get('/financings/{financing}/payments/create',[PembiayaanController::class, 'createPayment'])->middleware('permission:edit_murabahah')->name('financing.payments.create');
    Route::post('/financings/{financing}/payments/store', [PembiayaanController::class, 'storePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.store');
    Route::post('/financings/{financing}/payments/reschedule', [PembiayaanController::class, 'reschedulePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.reschedule');
    Route::get('/financings/{financing}/payments/create',[PembiayaanController::class, 'createPayment'])->middleware('permission:edit_murabahah')->name('financing.payments.create');
    Route::post('/financings/{financing}/payments/store', [PembiayaanController::class, 'storePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.store');
    Route::post('/financings/{financing}/payments/reschedule', [PembiayaanController::class, 'reschedulePayment'])->middleware('permission:edit_murabahah')->name('financing.payments.reschedule');

    // Pengelolaan Akun
    Route::get('/accounts', [AkunController::class, 'index'])->middleware('permission:view_kas')->name('accounts.index');
    Route::post('/accounts/create', [AkunController::class, 'store'])->middleware('permission:create_kas')->name('accounts.create');
    Route::patch('/accounts/{id}/status', [AkunController::class, 'updateStatus'])->middleware('permission:edit_kas')->name('accounts.update-status');

    // Pengelolaan Kas
    Route::get('/kas', [AruskasController::class, 'index'])->middleware('permission:view_kas')->name('kas.index');
    Route::post('/kas/store', [AruskasController::class, 'store'])->middleware('permission:create_kas')->name('kas.store');
    Route::get('/kas/export/excel',[AruskasController::class, 'exportExcel'])->name('kas.export.excel');

    // Pengaturan Umum
    Route::get('/settings', [PengaturanUmumController::class, 'index'])->middleware('permission:view_pengaturan')->name('settings.index');
    Route::post('/settings', [PengaturanUmumController::class, 'store'])->middleware('permission:create_pengaturan|edit_pengaturan')->name('settings.store');

    // Personal
    Route::get('/dashboard', [DasborController::class, 'index'])->name('dashboard');
    Route::get('/profile', [PengurusController::class, 'showProfil'])->name('profile.show');
    Route::get('/profile/edit', [PengurusController::class, 'editProfil'])->name('profile.edit');
    Route::put('/profile/update', [PengurusController::class, 'updateProfil'])->name('profile.update');

    // Notifikasi
    Route::get('/notifications', [NotifikasiController::class, 'index'])->middleware('permission:view_notifikasi')->name('notifications.index');

    // Peran dan Akses
    Route::get('/roles', [PeranAksesController::class, 'index'])->middleware('permission:view_peran_akses')->name('roles.index');
    Route::get('/roles/create', [PeranAksesController::class, 'create'])->middleware('permission:create_peran_akses')->name('roles.create');
    Route::post('/roles', [PeranAksesController::class, 'store'])->middleware('permission:create_peran_akses')->name('roles.store');
    Route::get('/roles/{id}', [PeranAksesController::class, 'show'])->middleware('permission:view_peran_akses')->name('roles.show');
    Route::get('/roles/{id}/edit', [PeranAksesController::class, 'edit'])->middleware('permission:edit_peran_akses')->name('roles.edit');
    Route::put('/roles/{id}', [PeranAksesController::class, 'update'])->middleware('permission:edit_peran_akses')->name('roles.update');
});

// User Routes
Route::prefix('user')->name('user.')->middleware(['auth', 'role:Anggota', 'revalidate'])->group(function () {
    Route::get('/dashboard', [AnggotaController::class, 'index'])->name('userDashboard');

    Route::get('/profile', [AnggotaController::class, 'profileShow'])->name('profile.show');
    Route::get('/profile/edit', [AnggotaController::class, 'profileEdit'])->name('profile.edit');
    Route::put('/profile', [AnggotaController::class, 'profileUpdate'])->name('profile.update');
    Route::post('/profile/picture', [AnggotaController::class, 'updateProfilePicture'])->name('profile.picture.update');
    Route::delete('/profile/picture', [AnggotaController::class, 'deleteProfilePicture'])->name('profile.picture.delete');
    Route::post('/profile/update-password', [AnggotaController::class, 'updatePassword'])->name('profile.update-password');

    Route::get('/resign', [AnggotaController::class, 'createResign'])->name('resign.create');
    Route::post('/resign', [AnggotaController::class, 'storeResign'])->name('resign.store');

    // Notifikasi
    Route::get('/notifications', [UserNotifikasiController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/{notification}', [UserNotifikasiController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/mark-all-read', [UserNotifikasiController::class, 'markAllAsRead'])->name('notifications.markAllRead');
    Route::post('/notifications/mark-popup-displayed', [UserNotifikasiController::class, 'markPopupDisplayed'])->name('notifications.markPopupDisplayed');

    // Tabungan
    Route::get('/tabungan', [UserSimpananController::class, 'index'])->name('tabungan.index');
    Route::get('/tabungan/export', [UserSimpananController::class, 'export'])->name('tabungan.export');

    // Pembiayaan
    Route::get('/financings', [UserPembiayaanController::class, 'index'])->name('financing.index');
    Route::get('/financings/show/{id}', [UserPembiayaanController::class, 'show'])->name('financing.show');
});
