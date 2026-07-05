<?php

namespace App\Http\Requests;

use App\Enums\EducationEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {

        return [
            // Anggota data
            'anggota.kode_pengguna' => 'required|string|max:255',
            'anggota.nama' => 'required|string|max:255',
            'anggota.nik' => 'required|string|digits:16',
            'anggota.no_telp' => 'required|string|max:20',
            'anggota.email' => 'nullable|email|max:255',
            'anggota.tempat_lahir' => 'nullable|string|max:255',
            'anggota.tgl_lahir' => 'nullable|date',
            'anggota.jenis_kelamin' => 'nullable|in:' . implode(',', array_column(GenderEnum::cases(), 'value')),
            'anggota.status_pernikahan' => 'nullable|in:' . implode(',', array_column(MaritalStatusEnum::cases(), 'value')),
            'anggota.pendidikan_terakhir' => 'nullable|in:' . implode(',', array_column(EducationEnum::cases(), 'value')),
            'anggota.alamat_domisili' => 'nullable|string|max:500',
            'anggota.alamat_ktp' => 'nullable|string|max:500',
            'anggota.jml_tanggungan' => 'nullable|integer|min:0',
            'anggota.status_pekerjaan' => 'nullable|string|max:255',
            'anggota.jabatan_pekerjaan' => 'nullable|string|max:255',
            'anggota.nama_perusahaan' => 'nullable|string|max:255',
            'anggota.bidang_usaha' => 'nullable|string|max:500',
            'anggota.lama_bekerja' => 'nullable|integer|min:0',
            'anggota.alamat_tempat_bekerja' => 'nullable|string|max:500',
            'anggota.no_telp_kantor' => 'nullable|string|max:20',
            'anggota.ahli_waris.*.nama_ahli_waris' => 'required|string|max:255',
            'anggota.ahli_waris.*.nik_ahli_waris' => 'required|string|digits:16',
            'anggota.ahli_waris.*.hubungan' => 'required|string|max:255',
            'anggota.ahli_waris.*.kontak_ahli_waris' => 'required|string|max:20',
            'anggota.jml_gaji_pokok' => 'nullable|numeric|min:0',
            'anggota.jml_penghasilan_usaha' => 'nullable|numeric|min:0',
            'anggota.jml_penghasilan_pasangan' => 'nullable|numeric|min:0',
            'anggota.jml_penghasilan_lainnya' => 'nullable|numeric|min:0',
            'anggota.jml_biaya_hidup_keluarga' => 'nullable|numeric|min:0',
            'anggota.jml_biaya_pendidikan' => 'nullable|numeric|min:0',
            'anggota.jml_cicilan' => 'nullable|numeric|min:0',
            'anggota.jml_biaya_lainnya' => 'nullable|numeric|min:0',

            // Pembiayaan data
            'pembiayaan.nama_barang' => 'required|string|max:255',
            'pembiayaan.jenis_barang_id' => 'required|exists:jenis_barang,id',
            'pembiayaan.kondisi_produk' => 'required|string|max:255',
            'pembiayaan.kuantitas' => 'required|integer|min:1',
            'pembiayaan.spesifikasi_barang' => 'required|string|max:1000',
            'pembiayaan.harga_beli_per_unit' => 'required|numeric|min:0',
            'pembiayaan.harga_perolehan' => 'required|numeric|min:0',
            'pembiayaan.margin_keuntungan' => 'required|numeric|min:0',
            'pembiayaan.metode_pembayaran' => 'required|string|max:255',
            'pembiayaan.tgl_akad' => 'required|date',
            'pembiayaan.uang_muka' => 'nullable|numeric|min:0',
            'pembiayaan.status' => 'required|string|max:255',
            'pembiayaan.tenor' => 'nullable|integer',
            'pembiayaan.akad_wakalah_date' => 'nullable|date',
            'pembiayaan.harga_perkiraan' => 'required|numeric|min:0',
            'pembiayaan.pemasok_id' => 'required|exists:pemasok,id',
            'pembiayaan.tangguh_tgl_pembayaran' => 'nullable|date|after_or_equal:pembiayaan.tgl_akad',

            // Collateral data
            'collateral.collateral_type' => 'nullable|string|max:255',
            'collateral.owner_name' => 'nullable|string|max:255',
            'collateral.estimated_market_value' => 'nullable|numeric|min:0',
            'collateral.collateral_location' => 'nullable|string|max:500',

            // Pemasok data
            'pemasok.nama_pemasok' => 'required|string|max:255',
            'pemasok.alamat_pemasok' => 'required|string|max:500',
            'pemasok.contact' => 'nullable|string|max:255',

            // File uploads
            'income_slip_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'bank_book_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'purchase_receipt_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'akad_document_file' => 'nullable|file|mimes:pdf|max:2048',
            'akad_wakalah_file' => 'nullable|file|mimes:pdf|max:2048',
        ];
    }
}
