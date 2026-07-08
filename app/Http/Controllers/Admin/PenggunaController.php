<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EducationEnum;
use App\Enums\AhliWarisEnum;
use App\Enums\InstallmentPaymentScheduleStatusEnum;
use App\Enums\MaritalStatusEnum;
use App\Enums\UserStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMemberAllocationRequest;
use App\Http\Requests\StoreMemberRequest;
use App\Http\Requests\UpdateMemberRequest;
use App\Models\Pengguna;
use App\Services\Admin\AnggotaService;
use App\Services\Admin\PembiayaanService;
use App\Services\User\AlokasiAnggotaService;
use App\Services\User\PendaftaranAnggotaService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use RuntimeException;

class PenggunaController extends Controller
{

    public function __construct(
        protected AnggotaService $anggotaService
    ) {}

    public function create()
    {
        return Inertia::render('Admin/User/Create/Index', [
            'pendidikanOptions' => $this->enumOptions(EducationEnum::cases()),
            'statusPernikahanOptions' => $this->enumOptions(MaritalStatusEnum::cases()),
            'hubunganOptions' => $this->enumOptions(AhliWarisEnum::cases()),
        ]);
    }

    public function store(StoreMemberRequest $request, PendaftaranAnggotaService $pendaftaranAnggotaService)
    {
        $validated = $request->validated();

        try {
            $memberCredentials = $pendaftaranAnggotaService->register($validated, $request);
        } catch (RuntimeException $e) {
            return back()->withErrors([
                'anggota' => $e->getMessage(),
            ]);
        }

        return redirect()->route('admin.users.index')->with([
            'success' => 'Anggota berhasil ditambahkan.',
            'member_credentials' => $memberCredentials,
        ]);
    }

    public function allocation(Request $request, AlokasiAnggotaService $alokasiAnggotaService)
    {
        return Inertia::render('Admin/User/Allocation/Index', $alokasiAnggotaService->buildPageData($request));
    }

    public function storeAllocation(StoreMemberAllocationRequest $request, AlokasiAnggotaService $alokasiAnggotaService)
    {
        $alokasiAnggotaService->allocate($request->validated());

        return redirect()->route('admin.allocation')->with('success', 'Alokasi anggota berhasil disimpan.');
    }

    private function enumOptions(array $cases): array
    {
        return collect($cases)
            ->map(fn($item) => [
                'value' => $item->value,
                'text' => $item->value,
            ])
            ->values()
            ->all();
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return Inertia::render('Admin/User/List', [
            'anggota' => $this->anggotaService->getListAnggota($request),
            'filters' => $request->only(['search', 'status', 'per_page', 'sort_by', 'sort_dir']),
            'summary' => $this->anggotaService->getSummary(),
            'statuses' => array_column(UserStatusEnum::cases(), 'value'),
            'can' => [
                'tambah_anggota' => Auth::user()->hasPermissionTo('create_anggota'),
                'edit_anggota'   => Auth::user()->hasPermissionTo('edit_anggota'),
            ],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id, PembiayaanService $service)
    {
        $ktpDoc = null;
        $kkDoc = null;

        $user = $this->anggotaService->getDetailAnggota($id);

        if ($user->anggota) {
            $ktpDoc = $user->anggota->dokumenAnggota->where('nama_dokumen', 'ktp')->first();
            $kkDoc = $user->anggota->dokumenAnggota->where('nama_dokumen', 'kartu_keluarga')->first();

            if ($user->anggota->pembiayaan) {
                $user->anggota->pembiayaan->each(function ($pembiayaan) use ($service) {
                    $service->computepembiayaanummary($pembiayaan);
                    $nextInstallment = $pembiayaan->angsuran
                    ->where('status', InstallmentPaymentScheduleStatusEnum::SCHEDULED->value)
                    ->sortBy('tgl_jatuh_tempo')
                    ->first();

                $pembiayaan->setAttribute('next_due_date', $nextInstallment?->tgl_jatuh_tempo);
                });
            }
        }

        return inertia('Admin/User/Show', [
            'user' => $user,
            'ktp_photo' => $ktpDoc?->lampiran_dokumen ? asset('storage/' . $ktpDoc->lampiran_dokumen) : null,
            'kk_photo' => $kkDoc?->lampiran_dokumen ? asset('storage/' . $kkDoc->lampiran_dokumen) : null,
        ]);
    }

    public function edit(string $id)
    {
        $user = $this->anggotaService->getDetailAnggota($id);

        $user->kk = $user->anggota?->dokumenAnggota?->firstWhere('nama_dokumen', 'kartu_keluarga')?->lampiran_dokumen
            ? asset('storage/' . $user->anggota->dokumenAnggota->firstWhere('nama_dokumen', 'kartu_keluarga')->lampiran_dokumen)
            : null;

        $user->ktp = $user->anggota?->dokumenAnggota?->firstWhere('nama_dokumen', 'ktp')?->lampiran_dokumen
            ? asset('storage/' . $user->anggota->dokumenAnggota->firstWhere('nama_dokumen', 'ktp')->lampiran_dokumen)
            : null;

        return inertia('Admin/User/Edit', [
            'data' => $user,
            'opsiPendidikan' => $this->enumOptions(EducationEnum::cases()),
            'opsiStatusPerkawinan' => $this->enumOptions(MaritalStatusEnum::cases()),
            'opsiHubunganKeluarga' => $this->enumOptions(AhliWarisEnum::cases()),
        ]);
    }

    public function update(UpdateMemberRequest $request, string $id)
    {
        $validated = $request->validated();

        $user = $this->anggotaService->getDetailAnggota($id);

        try {
            $this->anggotaService->updateMemberData($user, $validated);

            return redirect()->route('admin.users.index');
        } catch (Exception $e) {
            Log::info('error'. $e->getMessage());
            return back()->withErrors([
                'anggota' => $e->getMessage(),
            ]);
        }
    }

    public function getMutasi($accountId)
    {
        $account = $this->anggotaService->getMutasiSimpananAnggota($accountId);

        return response()->json($account->transactions);
    }

    public function getRiwayat($financingId)
    {
        $pembiayaan = $this->anggotaService->getRiwayatPembiayaanAnggota($financingId);

        if ($pembiayaan->angsuran->isEmpty()) {
            return response()->json([]);
        }

        return response()->json($pembiayaan->angsuran);
    }

    public function verificationDetail(Pengguna $user)
    {
        $user->load('userDocs');

        $photoUrl = $user->foto_profil ? asset('storage/' . $user->foto_profil) : null;
        $idCard = $user->userDocs
            ->firstWhere('name', 'ktp');
        $idCardUrl = $idCard?->attachment ? asset('storage/' . $idCard->attachment) : null;

        return Inertia::render('Admin/User/Verification/Show', [
            'anggota' => [
                'id' => $user->id,
                'kode_pengguna' => $user->kode_pengguna,
                'nama' => $user->nama,
                'nik' => $user->nik,
                'email' => $user->email,
                'photo_url' => $photoUrl,
                'id_card_url' => $idCardUrl,
            ],
        ]);
    }

    public function updateStatusToInactive(string $id)
    {
        $user = Pengguna::findOrFail($id);
        $user->update([
            'status' => UserStatusEnum::INACTIVE,
        ]);

        return redirect()->back();
    }
}
