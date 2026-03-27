<?php

namespace App\Http\Requests;

use App\Enums\Education;
use App\Enums\Heir as HeirEnum;
use App\Enums\MaritalStatus;
use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare incoming data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? trim((string) $this->input('email')) : null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'nik' => 'required|digits:16|unique:users,nik',
            'birth_place' => 'required|string|max:255',
            'birth_date' => 'required|date',
            'marital_status' => 'required|in:' . implode(',', array_column(MaritalStatus::cases(), 'value')),
            'phone_number' => 'required|string|max:20|unique:users,phone_number',
            'email' => 'nullable|email|max:255|unique:users,email',
            'address' => 'required|string|max:500',
            'residential_address' => 'nullable|string|max:500',
            'last_education' => 'required|in:' . implode(',', array_column(Education::cases(), 'value')),
            'heir_nik' => 'required|digits:16|unique:heirs,nik',
            'heir_name' => 'required|string|max:255',
            'heir_relationship' => 'required|in:' . implode(',', array_column(HeirEnum::cases(), 'value')),
            'heir_contact' => 'required|string|max:20',
            'ktp_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'kk_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ];
    }
}
