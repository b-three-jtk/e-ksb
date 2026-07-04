<?php

namespace App\Http\Requests\Admin;

use App\Enums\EducationEnum;
use App\Enums\GenderEnum;
use App\Enums\MaritalStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePreFinancingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
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

            'anggota.employment_status' => 'required|string|max:255',
            'anggota.job_title' => 'nullable|string|max:255',
            'anggota.company_or_business_name' => 'nullable|string|max:255',
            'anggota.business_field' => 'nullable|string|max:500',
            'anggota.tenure_year' => 'nullable|integer|min:0',
            'anggota.workplace_address' => 'nullable|string|max:500',
            'anggota.workplace_contact' => 'nullable|string|max:20',

            'anggota.heirs.*.heir_name' => 'required|string|max:255',
            'anggota.heirs.*.heir_nik' => 'required|string|digits:16',
            'anggota.heirs.*.relationship' => 'required|string|max:255',
            'anggota.heirs.*.heir_contact' => 'required|string|max:20',
            'anggota.gaji_pokok_amount' => 'nullable|numeric|min:0',
            'anggota.penghasilan_usaha_amount' => 'nullable|numeric|min:0',
            'anggota.penghasilan_pasangan_amount' => 'nullable|numeric|min:0',
            'anggota.penghasilan_lainnya_amount' => 'nullable|numeric|min:0',
            'anggota.biaya_hidup_keluarga_amount' => 'nullable|numeric|min:0',
            'anggota.biaya_pendidikan_amount' => 'nullable|numeric|min:0',
            'anggota.jumlah_cicilan_amount' => 'nullable|numeric|min:0',
            'anggota.jumlah_biaya_lainnya_amount' => 'nullable|numeric|min:0',

            // Pembiayaan data
            'pembiayaan.name' => 'required|string|max:255',
            'pembiayaan.jenis_barang_id' => 'required|exists:jenis_barang,id',
            'pembiayaan.condition' => 'required|string|max:255',
            'pembiayaan.qty' => 'required|integer|min:1',
            'pembiayaan.specification' => 'required|string|max:1000',
            'pembiayaan.status' => 'required|string|max:255',
            'pembiayaan.harga_perkiraan' => 'required|numeric|min:0',
            'pembiayaan.uang_muka' => 'nullable|numeric|min:0',

            // Collateral data
            'collateral.collateral_type' => 'required|string|max:255',
            'collateral.owner_name' => 'required|string|max:255',
            'collateral.estimated_market_value' => 'nullable|numeric|min:0',
            'collateral.collateral_location' => 'nullable|string|max:500',

            // File uploads
            'income_slip_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'bank_book_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ];
    }
}
