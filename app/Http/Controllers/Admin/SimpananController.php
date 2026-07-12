<?php

namespace App\Http\Controllers\Admin;

use App\Enums\MemberStatusEnum;
use App\Enums\SavingTypeEnum;
use App\Enums\TransactionTypeEnum;
use App\Enums\UserRoleEnum;
use App\Enums\UserStatusEnum;
use App\Enums\PositionEnum;
use App\Exports\SimpananExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreDepositRequest;
use App\Http\Requests\StoreWithdrawalRequest;
use App\Models\AkunBerjangka;
use App\Models\AkunIbadah;
use App\Models\Anggota;
use App\Models\RekeningAnggota;
use App\Models\AkunSimpanan;
use App\Models\TransaksiSimpanan;
use App\Models\Akun;
use App\Services\Admin\JurnalService;
use App\Services\User\SimpananServices;
use App\Services\PengaturanUmumService;
use App\Services\Admin\SimpananService;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SimpananController extends Controller
{
    public function __construct(
        private SimpananServices $simpananServices,
        private PengaturanUmumService $pengaturanUmumService,
        private SimpananService $simpananService,
    ) {
    }

    public function index(Request $request)
    {
        $data = $this->simpananService->getTransactionList($request);

        return Inertia::render('Admin/Savings/List', $data);
    }

    public function exportExcel(Request $request)
    {
        $tab = $request->input('tab', 'semua');

        $title = $this->simpananService->getExportTitle($tab);

        $transactions = $this->simpananService
            ->buildBaseQuery($request)
            ->orderBy('tgl_transaksi', 'desc')
            ->get();

        return Excel::download(
            new SimpananExport($transactions, $title),
            'data_simpanan_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $tab   = $request->input('tab', 'semua');
        $title = $this->simpananService->getExportTitle($tab);

        $transactions = $this->simpananService->buildBaseQuery($request)
            ->orderBy('tgl_transaksi', 'desc')
            ->get();

        $pdf = Pdf::loadView('exports.saving', [
            'transactions' => $transactions,
            'title'        => $title,
        ])->setPaper('a4', 'landscape');

        return $pdf->download(Str::slug($title) . '_' . now()->format('Ymd_His') . '.pdf');
    }

    public function show(string $id)
    {
        $anggota = TransaksiSimpanan::with('akunSimpanan.anggota.user')->findOrFail($id)->akunSimpanan->anggota;
        if (Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value) && $anggota->pj_anggota_id !== Auth::id()) {
            abort(403, 'Anda tidak memiliki izin untuk melihat detail transaksi simpanan ini.');
        }

        $data = TransaksiSimpanan::with('akunSimpanan.anggota.user', 'rekeningAnggota')->find($id);
        $struk_simpanan = $data->struk_simpanan ? Storage::url($data->struk_simpanan) : null;

        return inertia('Admin/Savings/Show', [
            'data' => $data,
            'struk_simpanan' => $struk_simpanan,
        ]);
    }

    public function verifyTransaction(Request $request, string $id)
    {
        if (!Auth::user()->hasRole(UserRoleEnum::BENDAHARA->value) && !Auth::user()->hasRole(UserRoleEnum::ADMIN->value)) {
            abort(403, 'Anda tidak memiliki izin untuk memverifikasi transaksi ini.');
        }

        try {
            $trx = $this->simpananService->verifyTransaction($id, Auth::id());

            $message = $trx->tipe_transaksi === TransactionTypeEnum::WITHDRAWAL->value
                ? 'Penarikan berhasil diverifikasi, saldo telah dikurangi dan jurnal telah dibuat.'
                : 'Transaksi simpanan berhasil diverifikasi dan jurnal telah dibuat.';

            return back()->with('success', $message);
        } catch (\Throwable $th) {
            return back()->withErrors(['error' => 'Gagal memverifikasi transaksi: ' . $th->getMessage()]);
        }
    }

    public function createDeposit(Request $request)
    {
        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'anggota'      => $this->simpananService->getMembersForDeposit(),
            'jenis_simpanans' => collect(SavingTypeEnum::cases())->map(fn($c) => $c->value),
            'pengurus'     => ['nama' => Auth::user()->nama ?? 'Pengurus'],
            'global_saving' => [
                'pokok' => $this->simpananService->getSettingValue('saving_pokok_amount'),
                'wajib' => $this->simpananService->getSettingValue('saving_wajib_amount'),
            ],
        ]);
    }

    public function storeRekening(Request $request)
    {
        $data = $request->validate([
            'no_rekening' => 'required|string|max:50',
            'nama_bank'   => 'required|string|max:100',
            'atas_nama'   => 'required|string|max:100',
            'anggota_id'  => 'required|exists:anggota,id',
        ]);

        if (RekeningAnggota::where('no_rekening', $data['no_rekening'])->exists()) {
            return response()->json([
                'message' => 'Nomor rekening sudah terdaftar.'
            ], 422);
        }

        $rekening = RekeningAnggota::create($data);

        return response()->json($rekening);
    }

    public function storeDeposit(StoreDepositRequest $request)
    {
        $data = $request->validated();

        if (in_array($data['saving_category'], [
            SavingTypeEnum::SIMPANAN_POKOK->value,
            SavingTypeEnum::SIMPANAN_WAJIB->value,
        ])) {
            $data['nominal_angsuran'] = $this->simpananService->getSettingValue(
                $data['saving_category'] === SavingTypeEnum::SIMPANAN_POKOK->value
                    ? 'saving_pokok_amount'
                    : 'saving_wajib_amount'
            );
        }

        $anggota = Anggota::with('user')->findOrFail($data['anggota_id']);

        if (Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value) && $anggota->pj_anggota_id !== Auth::id()) {
            abort(403, 'Anda tidak berhak melakukan transaksi untuk anggota ini.');
        }

        $akunSimpanan = $this->simpananService->resolveOrCreateSavingAccount($data, $anggota);
        $this->simpananService->resolveOrCreateMemberBankAccount($data, $anggota);

        Log::info('Saving account for anggota', [
            'anggota_id'            => $anggota->id,
            'akun_simpanan_id'    => $akunSimpanan->id,
            'was_recently_created' => $akunSimpanan->wasRecentlyCreated,
        ]);

        $this->simpananService->validateDepositRules($data, $akunSimpanan, $anggota);

        $saldoSebelumnya = $akunSimpanan->saldo;
        $transaction = $this->simpananService->createDepositTransaction($data, $akunSimpanan, $anggota);

        Log::info('Deposit transaction created', [
            'transaction_id'    => $transaction->id,
            'akun_simpanan_id' => $akunSimpanan->id,
            'amount'            => $transaction->nominal_simpanan,
            'new_balance'       => $transaction->saldo_setelah_transaksi,
        ]);

        $strukData = [
            'no_transaksi'  => $transaction->kode_transaksi_simpanan,
            'tanggal'       => $transaction->tgl_transaksi,
            'pengurus'      => Auth::user()->nama,
            'nama_anggota'  => $anggota->user->nama,
            'no_anggota'    => $anggota->user->kode_pengguna,
            'jenis'         => $data['saving_category'],
            'metode'        => $transaction->metode_pembayaran_simpanan,
            'nominal'       => $transaction->nominal_simpanan,
            'saldo_sebelum' => $saldoSebelumnya,
            'saldo_sesudah' => $saldoSebelumnya + $transaction->nominal_simpanan,
            'tujuan'       => $data['tujuan'] ?? null,
            'nama_bank'     => $data['nama_bank'] ?? null,
            'atas_nama'     => $data['atas_nama'] ?? null,
            'no_rekening'   => $data['no_rekening'] ?? null,
        ];

        $this->simpananService->storeReceiptDepositPdf($transaction, $strukData, $anggota->id);
        $transaction->refresh();

        return Inertia::render('Admin/Savings/Penyetoran/Create', [
            'anggota'      => $this->simpananService->getMembersForDeposit(),
            'jenis_simpanans' => collect(SavingTypeEnum::cases())->map(fn($c) => $c->value),
            'pengurus'     => ['nama' => Auth::user()->nama ?? 'Pengurus'],
            'global_saving' => [
                'pokok' => $this->simpananService->getSettingValue('saving_pokok_amount'),
                'wajib' => $this->simpananService->getSettingValue('saving_wajib_amount'),
            ],
            'struk' => $strukData,
            'receipt' => Storage::url($transaction->struk_simpanan),
        ]);
    }

    public function createWithdrawal()
    {
        $anggota = $this->getMembersForSavingSelection(true);

        return Inertia::render('Admin/Savings/Withdrawal/Create', [
            'anggota' => $anggota,
        ]);
    }

    public function storeWithdrawal(StoreWithdrawalRequest $request)
    {
        try {
            $anggota = Anggota::with('user')->findOrFail($request->anggota_id);
            if (Auth::user()->hasRole(UserRoleEnum::PJANGGOTA->value) && $anggota->pj_anggota_id !== Auth::id()) {
                abort(403, 'Anda tidak berhak melakukan transaksi untuk anggota ini.');
            }

            $result = $this->simpananServices->storeWithdrawal(
                $request->validated(),
                Auth::id()
            );

            $isBendahara = Auth::user()->hasRole(UserRoleEnum::BENDAHARA->value)
                || Auth::user()->hasRole(UserRoleEnum::ADMIN->value);

            if ($isBendahara && !empty($result['struk']['transaction_id'])) {
                $this->simpananService->verifyTransaction($result['struk']['transaction_id'], Auth::id());
            }

            return redirect()
                ->route('admin.savings.withdrawal.create')
                ->with('struk', $result['struk'])
                ->with('receipt', $result['receipt']);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        } catch (\Exception $e) {
            report($e);
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    private function getMembersForSavingSelection(bool $includeBankAccounts = false)
    {
        $query = Anggota::query()
            ->when($includeBankAccounts, function ($q) {
                $q->with([
                    'user',
                    'akunSimpanan.ibadah',
                    'akunSimpanan.berjangka',
                    'bankAccounts' => function ($subQuery) {
                        $subQuery->latest();
                    },
                ]);
            }, function ($q) {
                $q->with(['user:id,kode_pengguna,nama', 'akunSimpanan.ibadah', 'akunSimpanan.berjangka']);
            })
            ->whereIn('status', [
                MemberStatusEnum::ACTIVE->value,
                MemberStatusEnum::PAYMENT_PENDING->value,
            ])
            ->whereHas('user', function ($q) {
                $q->where('status', UserStatusEnum::ACTIVE->value);
            });

        if (Auth::user()?->hasRole(UserRoleEnum::PJANGGOTA->value)) {
            $query->where('pj_anggota_id', Auth::id());
        }

        return $query->get()->map(function ($anggota) use ($includeBankAccounts) {
            if ($includeBankAccounts) {
                return [
                    'id' => $anggota->id,
                    'nama' => $anggota->user?->nama,
                    'kode_pengguna' => $anggota->user?->kode_pengguna,
                    'akunSimpanan' => $anggota->akunSimpanan->map(function ($acc) {
                        return [
                            'id' => $acc->id,
                            'type' => $acc->jenis_simpanan ?? '-',
                            'saldo' => $acc->saldo ?? 0,
                            'tenor_months' => $acc->berjangka?->tenor,
                            'target_tabungan' => $acc->ibadah?->target_tabungan,
                            'opened_at' => optional($acc->created_at)->toDateString(),
                        ];
                    })->toArray(),
                    'akun' => $anggota->bankAccounts->map(function ($acc) {
                        return [
                            'nama_bank' => $acc->nama_bank,
                            'atas_nama' => $acc->atas_nama,
                            'no_rekening' => $acc->no_rekening,
                        ];
                    })->toArray(),
                    'bukti_penyetoran' => [
                        'nullable',
                        'file',
                        'mimes:jpg,jpeg,png,pdf',
                        'max:5120',
                    ],
                ];
            }

            return [
                'id' => $anggota->id,
                'kode_pengguna' => $anggota->user->kode_pengguna,
                'nama' => $anggota->user->nama,
                'status' => $anggota->status,
                'akunSimpanan' => $anggota->akunSimpanan->map(fn($acc) => [
                    'type' => $acc->jenis_simpanan ?? null,
                    'tujuan' => $acc->ibadah?->tujuan ?? $acc->berjangka?->tujuan ?? null,
                    'saldo' => $acc->saldo ?? 0,
                    'target_tabungan' => $acc->ibadah?->target_tabungan ?? null,
                    'matured_at' => $acc->berjangka?->tenor && $acc->created_at
                        ? $acc->created_at->copy()->addMonths($acc->berjangka->tenor)->format('d M Y')
                        : null,
                    'is_frozen' => !is_null($acc->ibadah?->target_tabungan) && $acc->saldo >= $acc->ibadah->target_tabungan,
                    'is_matured' => $acc->berjangka?->tenor && $acc->created_at
                        ? now()->gte($acc->created_at->copy()->addMonths($acc->berjangka->tenor))
                        : false,
                ]),
            ];
        });
    }
}
