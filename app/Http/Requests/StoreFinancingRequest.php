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
            'anggota.employment_status' => 'nullable|string|max:255',
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

            // Financing data
            'financing.name' => 'required|string|max:255',
            'financing.product_type_id' => 'required|exists:product_types,id',
            'financing.condition' => 'required|string|max:255',
            'financing.qty' => 'required|integer|min:1',
            'financing.specification' => 'required|string|max:1000',
            'financing.price_per_unit' => 'required|numeric|min:0',
            'financing.cost_price' => 'required|numeric|min:0',
            'financing.margin_amount' => 'required|numeric|min:0',
            'financing.payment_method' => 'required|string|max:255',
            'financing.akad_date' => 'required|date',
            'financing.down_payment' => 'nullable|numeric|min:0',
            'financing.status' => 'required|string|max:255',
            'financing.tenor' => 'nullable|integer',
            'financing.akad_wakalah_date' => 'nullable|date',
            'financing.predicted_cost_price' => 'required|numeric|min:0',
            'financing.supplier_id' => 'required|exists:suppliers,id',
            'financing.tangguh_payment_date' => 'nullable|date|after_or_equal:financing.akad_date',

            // Collateral data
            'collateral.collateral_type' => 'nullable|string|max:255',
            'collateral.owner_name' => 'nullable|string|max:255',
            'collateral.estimated_market_value' => 'nullable|numeric|min:0',
            'collateral.collateral_location' => 'nullable|string|max:500',

            // Supplier data
            'supplier.supplier_name' => 'required|string|max:255',
            'supplier.address' => 'required|string|max:500',
            'supplier.contact' => 'nullable|string|max:255',

            // File uploads
            'income_slip_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'bank_book_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'purchase_receipt_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'akad_document_file' => 'nullable|file|mimes:pdf|max:2048',
            'akad_wakalah_file' => 'nullable|file|mimes:pdf|max:2048',
        ];
    }
}
