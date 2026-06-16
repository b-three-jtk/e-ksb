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
            // Member data
            'member.user_code' => 'required|string|max:255',
            'member.name' => 'required|string|max:255',
            'member.nik' => 'required|string|digits:16',
            'member.phone_number' => 'required|string|max:20',
            'member.email' => 'nullable|email|max:255',
            'member.birth_place' => 'nullable|string|max:255',
            'member.birth_date' => 'nullable|date',
            'member.gender' => 'nullable|in:' . implode(',', array_column(GenderEnum::cases(), 'value')),
            'member.marital_status' => 'nullable|in:' . implode(',', array_column(MaritalStatusEnum::cases(), 'value')),
            'member.last_education' => 'nullable|in:' . implode(',', array_column(EducationEnum::cases(), 'value')),
            'member.domicile_address' => 'nullable|string|max:500',
            'member.residential_address' => 'nullable|string|max:500',
            'member.dependents' => 'nullable|integer|min:0',
            'member.employment_status' => 'nullable|string|max:255',
            'member.job_title' => 'nullable|string|max:255',
            'member.company_or_business_name' => 'nullable|string|max:255',
            'member.business_field' => 'nullable|string|max:500',
            'member.tenure_year' => 'nullable|integer|min:0',
            'member.workplace_address' => 'nullable|string|max:500',
            'member.workplace_contact' => 'nullable|string|max:20',
            'member.heirs.*.heir_name' => 'required|string|max:255',
            'member.heirs.*.heir_nik' => 'required|string|digits:16',
            'member.heirs.*.relationship' => 'required|string|max:255',
            'member.heirs.*.heir_contact' => 'required|string|max:20',
            'member.gaji_pokok_amount' => 'nullable|numeric|min:0',
            'member.penghasilan_usaha_amount' => 'nullable|numeric|min:0',
            'member.penghasilan_pasangan_amount' => 'nullable|numeric|min:0',
            'member.penghasilan_lainnya_amount' => 'nullable|numeric|min:0',
            'member.biaya_hidup_keluarga_amount' => 'nullable|numeric|min:0',
            'member.biaya_pendidikan_amount' => 'nullable|numeric|min:0',
            'member.jumlah_cicilan_amount' => 'nullable|numeric|min:0',
            'member.jumlah_biaya_lainnya_amount' => 'nullable|numeric|min:0',

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
