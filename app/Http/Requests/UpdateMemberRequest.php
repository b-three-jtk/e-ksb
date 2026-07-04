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
            'nik' => 'required|digits:16|unique:pengguna,nik,' . $this->route('id'),
            'nama' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:pengguna,email,' . $this->route('id'),
            'no_telp' => 'required|string|max:20',
            'jenis_kelamin' => 'nullable|in:'. implode(',', ['Laki-laki', 'Perempuan']),
            'tempat_lahir' => 'required|string|max:255',
            'tgl_lahir' => 'required|date',
            'alamat_ktp' => 'nullable|string|max:255',
            'alamat_domisili' => 'required|string|max:255',
            'pendidikan_terakhir' => 'required|string|max:255|in:'. implode(',', array_column(EducationEnum::cases(), 'value')),
            'status_pernikahan' => 'required|string|max:255|in:'. implode(',', array_column(MaritalStatusEnum::cases(), 'value')),
            'jml_tanggungan' => 'nullable|integer|min:0',

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
