<?php

namespace App\Http\Requests;

use App\Enums\EducationEnum;
use App\Enums\MaritalStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberRequest extends FormRequest
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
            'nik' => 'required|digits:16|unique:users,nik,' . $this->route('id'),
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:users,email,' . $this->route('id'),
            'phone_number' => 'required|string|max:20',
            'gender' => 'nullable|in:'. implode(',', ['Laki-laki', 'Perempuan']),
            'birth_place' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'residential_address' => 'nullable|string|max:255',
            'domicile_address' => 'required|string|max:255',
            'last_education' => 'required|string|max:255|in:'. implode(',', array_column(EducationEnum::cases(), 'value')),
            'marital_status' => 'required|string|max:255|in:'. implode(',', array_column(MaritalStatusEnum::cases(), 'value')),
            'dependents' => 'nullable|integer|min:0',

            'ktp_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'kk_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',

            'heirs' => 'nullable|array',
            'heirs.*.heir_nik' => 'required|string|max:16',
            'heirs.*.heir_name' => 'required|string|max:255',
            'heirs.*.relationship' => 'required|string',
            'heirs.*.heir_contact' => 'required|string|max:20',
        ];
    }
}
