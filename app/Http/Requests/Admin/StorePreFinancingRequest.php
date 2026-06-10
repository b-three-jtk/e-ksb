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
            'financing.status' => 'required|string|max:255',
            'financing.predicted_cost_price' => 'required|numeric|min:0',

            // Collateral data
            'collateral.collateral_type' => 'nullable|string|max:255',
            'collateral.owner_name' => 'nullable|string|max:255',
            'collateral.estimated_market_value' => 'nullable|numeric|min:0',
            'collateral.collateral_location' => 'nullable|string|max:500',

            // File uploads
            'income_slip_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'bank_book_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
        ];;
    }
}
