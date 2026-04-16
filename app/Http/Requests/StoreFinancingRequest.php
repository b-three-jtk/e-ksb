<?php

namespace App\Http\Requests;

use App\Enums\Education;
use App\Enums\Gender;
use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreFinancingRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'member.member_number' => 'required|exists:users,member_number',
            'member.name' => 'required|string|max:255',
            'member.nik' => 'required|string|digits:16',
            'member.birth_place' => 'nullable|string|max:255',
            'member.birth_date' => 'nullable|date',
            'member.gender' => 'nullable|in:'. implode(',', array_column(Gender::cases(), 'value')),
            'member.marital_status' => 'nullable|in:' . implode(',', array_column(MaritalStatus::cases(), 'value')),
            'member.last_education' => 'nullable|in:' . implode(',', array_column(Education::cases(), 'value')),
            'member.address' => 'nullable|string|max:500',
            'member.residential_address' => 'nullable|string|max:500',
            'member.phone_number' => 'nullable|string|max:20',
            'member.email' => 'nullable|email|max:255',
            'member.dependents' => 'nullable|integer|min:0',
            'member.heirs.*.name' => 'nullable|string|max:255',
            'member.heirs.*.nik' => 'nullable|string|digits:16',
            'member.heirs.*.relationship' => 'nullable|string|max:255',
            'member.heirs.*.contact' => 'nullable|string|max:20',
            'member.incomes.*.type' => 'nullable|string|max:255',
            'member.incomes.*.amount' => 'nullable|numeric|min:0',
            'member.expenses.*.type' => 'nullable|string|max:255',
            'member.expenses.*.amount' => 'nullable|numeric|min:0',
            'member.income_slip' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'member.family_card' => 'nullable|file|mimes:jpg,jpeg',
            'member.bank_book' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',

            'financing.product_name' => 'required|string|max:255',
            'financing.product_type' => 'required|string|max:255',
            'financing.brand' => 'nullable|string|max:255',
            'financing.color' => 'nullable|string|max:255',
            'financing.condition' => 'nullable|string|max:255',
            'financing.qty' => 'required|integer|min:1',
            'financing.cost_price' => 'required|numeric|min:0',
            'financing.down_payment' => 'nullable|numeric|min:0',
            'financing.description' => 'nullable|string|max:1000',
            'financing.down_payment_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'financing.procurement_proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'financing.akad_document' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',

            'supplier.name' => 'nullable|string|max:255',
            'supplier.contact' => 'nullable|string|max:255',
            'supplier.address' => 'nullable|string|max:500',
            'supplier.link_address' => 'nullable|url|max:255',
        ];
    }
}
