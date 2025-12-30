<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminRequest extends FormRequest
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
            'nik' => 'required|string|digits:16|unique:users,nik,' . $this->route('id'),
            'work_unit_id' => 'required|exists:work_units,id',
            'email' => 'required|email|max:255|unique:users,email,' . $this->route('id'),
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'institution' => 'required|string|max:255',
            'role_id' => 'required|exists:roles,id',
        ];
    }
}
