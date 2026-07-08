<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\PermissionRegistrar;

/**
 * @property \App\Models\Pengguna $userMember
 * @property \App\Models\Anggota $anggota
 * @property \App\Models\Pengguna $staffMurabahah
 * @property \App\Models\Pengguna $ketuaMurabahah
 * @property \App\Models\JenisBarang $jenisBarang
 * @property \App\Models\Pemasok $pemasok
 * @property array $payloadPengajuan
 */
abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
