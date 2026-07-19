<?php

use App\Enums\PositionEnum;
use App\Models\Akun;
use App\Models\Pengguna;
use App\Services\Admin\JurnalService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seed(\Database\Seeders\AkunSeeder::class);
    $this->jurnalService = new JurnalService();
});

describe('Validasi penjurnalan dengan sistem double entry', function () {
    it('Melempar exception jika total debit dan kredit tidak seimbang (tidak balance)', function () {
        $entries = [
            [
                'akun' => '1111001',
                'posisi_akun' => PositionEnum::DEBIT->value,
                'nominal' => 50000,
            ],
            [
                'akun' => '2111001',
                'posisi_akun' => PositionEnum::CREDIT->value,
                'nominal' => 40000, // Selisih 10.000
            ],
        ];

        expect(fn () => $this->jurnalService->create($entries))
            ->toThrow(\Exception::class, 'Total debit dan kredit harus seimbang.');
    });

    it('Berhasil menyimpan jurnal dan detail jurnal jika data valid dan seimbang', function () {
        $user = Pengguna::factory()->create();
        
        $akunDebit = Akun::first();
        $akunKredit = Akun::where('no_ref_akun', '!=', $akunDebit->no_ref_akun)->first();

        $entries = [
            [
                'akun' => $akunDebit->no_ref_akun,
                'posisi_akun' => PositionEnum::DEBIT->value,
                'nominal' => 100000.50,
            ],
            [
                'akun' => $akunKredit->no_ref_akun,
                'posisi_akun' => PositionEnum::CREDIT->value,
                'nominal' => 100000.50,
            ],
        ];

        $jurnalId = $this->jurnalService->create($entries, '2026-07-14', $user->id);

        expect($jurnalId)->not->toBeNull();

        $this->assertDatabaseHas('jurnal', [
            'id' => $jurnalId,
            'tgl_transaksi' => '2026-07-14',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('detail_jurnal', [
            'jurnal_id' => $jurnalId,
            'no_ref_akun' => $akunDebit->no_ref_akun,
            'posisi_akun' => PositionEnum::DEBIT->value,
            'nominal' => 100000.50,
            'updated_by' => $user->id,
        ]);

        $this->assertDatabaseHas('detail_jurnal', [
            'jurnal_id' => $jurnalId,
            'no_ref_akun' => $akunKredit->no_ref_akun,
            'posisi_akun' => PositionEnum::CREDIT->value,
            'nominal' => 100000.50,
            'updated_by' => $user->id,
        ]);
    });

});
