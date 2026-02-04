<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditProfileAdminRequest extends FormRequest
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
            'nik' => 'required|string|size:16',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'. $this->user()->id,
            'birth_place' => 'nullable|string|max:255',
            'birth_date' => 'nullable|date',
            'gender' => 'nullable|in:Laki-laki,Perempuan',
            'address' => 'nullable|string|max:500',
            'residential_address' => 'nullable|string|max:500',
            'phone_number' => 'nullable|string|size:16',
            'last_education' => 'nullable|string|max:100',
            'institution' => 'nullable|string|max:255',
            'work_unit_id' => 'required|exists:work_units,id',
            'profile_picture_file' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
        ];
    }
}
