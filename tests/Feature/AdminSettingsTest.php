<?php

use App\Enums\UserStatusEnum;
use App\Models\AuditLog;
use App\Models\Pengguna;
use Database\Seeders\PengaturanUmumSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Inertia\Testing\AssertableInertia;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RoleSeeder::class);
    $this->seed(PengaturanUmumSeeder::class);
});

// ============================================================================
// 1. Pengaturan Umum
// ============================================================================
describe('Pengaturan Umum', function () {
    it('Administrator sistem dapat menyimpan pengaturan umum koperasi', function () {
        $admin = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $admin->syncRoles('Administrator Sistem');

        $response = $this->actingAs($admin)
            ->post('/admin/settings', [
                'section' => 'general',
                'tanggal_awal_periode' => '2025-01-01',
                'tanggal_akhir_periode' => '2025-12-31',
                'status_tutup_buku' => 'open',
                'period_effective_date' => '2025-01-01',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('pengaturan_umum', [
            'key' => 'tanggal_awal_periode',
            'value' => '2025-01-01',
        ]);
        $this->assertDatabaseHas('pengaturan_umum', [
            'key' => 'status_tutup_buku',
            'value' => 'open',
        ]);
    });

    it('Menampilkan daftar dan riwayat pengaturan umum untuk peran tertentu', function () {
        $roles = [
            'Administrator Sistem',
            'Dewan Pengawas Syariah',
            'Pengawas',
            'Ketua',
            'Bendahara',
            'Sekretaris',
            'Ketua Murabahah',
        ];

        foreach ($roles as $roleName) {
            $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
            $user->syncRoles($roleName);

            $response = $this->actingAs($user)->get('/admin/settings');
            
            $response->assertStatus(200);
            $response->assertInertia(fn (AssertableInertia $page) =>
                $page->component('Admin/Settings/Index')
                    ->has('settings')
                    ->has('settingsHistory')
            );
        }
    });

    it('Peran yang tidak memiliki akses tidak dapat melihat pengaturan umum', function () {
        $anggota = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $anggota->syncRoles('Anggota');

        $response = $this->actingAs($anggota)->get('/admin/settings');
        $response->assertStatus(403);
    });
});

// ============================================================================
// 2. Hak Akses dan Peran (Roles)
// ============================================================================
describe('Hak Akses dan Peran', function () {
    it('Administrator sistem dapat menyimpan (memperbarui) hak akses dan peran', function () {
        $admin = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $admin->syncRoles('Administrator Sistem');

        $roleToUpdate = Role::where('name', 'Sekretaris')->first();
        
        $response = $this->actingAs($admin)
            ->put("/admin/roles/{$roleToUpdate->id}", [
                'name' => 'Sekretaris (Updated)',
                'permissions' => [], // Update to empty permissions
            ]);

        $response->assertStatus(302);
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('roles', [
            'id' => $roleToUpdate->id,
            'name' => 'Sekretaris (Updated)',
        ]);
    });

    it('Menampilkan daftar hak akses dan peran untuk peran tertentu', function () {
        $roles = [
            'Administrator Sistem',
            'Dewan Pengawas Syariah',
            'Pengawas',
            'Ketua',
        ];

        foreach ($roles as $roleName) {
            $user = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
            $user->syncRoles($roleName);

            $response = $this->actingAs($user)->get('/admin/roles');
            
            $response->assertStatus(200);
            $response->assertInertia(fn (AssertableInertia $page) =>
                $page->component('Admin/Roles/List')
                    ->has('roles.data')
            );
        }
    });

    it('Sekretaris tidak dapat melihat hak akses dan peran', function () {
        $sekretaris = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $sekretaris->syncRoles('Sekretaris');

        $response = $this->actingAs($sekretaris)->get('/admin/roles');
        $response->assertStatus(403);
    });
});

// ============================================================================
// 3. Log Aktivitas Koperasi
// ============================================================================
describe('Log Aktivitas Koperasi', function () {
    it('Administrator sistem dapat melihat daftar log aktivitas koperasi', function () {
        $admin = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $admin->syncRoles('Administrator Sistem');

        // Buat dummy log aktivitas
        AuditLog::create([
            'user_id' => $admin->id,
            'event' => 'login',
            'auditable_type' => 'App\Models\Pengguna',
            'auditable_id' => $admin->id,
            'old_values' => [],
            'new_values' => [],
            'url' => 'http://localhost/login',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Mozilla/5.0',
        ]);

        $response = $this->actingAs($admin)->get('/admin/logs');
        
        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) =>
            $page->component('Admin/AuditLog/Index')
                ->has('logs.data')
        );
    });

    it('Ketua tidak dapat melihat log aktivitas', function () {
        $ketua = Pengguna::factory()->create(['status' => UserStatusEnum::ACTIVE->value]);
        $ketua->syncRoles('Ketua');

        $response = $this->actingAs($ketua)->get('/admin/logs');
        $response->assertStatus(403);
    });
});
