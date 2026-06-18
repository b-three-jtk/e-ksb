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
            'email' => 'nullable|email|max:255|unique:users,email,'. $this->user()->id,
            'phone_number' => 'required|string',
            'profile_picture_file' => 'nullable|image|mimes:png,jpg,jpeg,gif|max:2048',
        ];
    }
}
