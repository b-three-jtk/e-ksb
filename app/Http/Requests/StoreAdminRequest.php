<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'nik' => 'required|string|unique:users,nik|digits:16',
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'required|in:Laki-laki,Perempuan',
            'email' => 'required|email|unique:users,email|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'residential_address' => 'nullable|string|max:500',
            'last_education' => 'nullable|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ];
    }
}
