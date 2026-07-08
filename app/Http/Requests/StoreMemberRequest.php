<?php

namespace App\Http\Requests;

use App\Enums\EducationEnum;
use App\Enums\AhliWarisEnum;
use App\Enums\MaritalStatusEnum;
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
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:Laki-laki,Perempuan',
            'nik' => 'required|digits:16|unique:pengguna,nik',
            'tempat_lahir' => 'required|string|max:255',
            'tgl_lahir' => 'required|date',
            'status_pernikahan' => 'required|in:' . implode(',', array_column(MaritalStatusEnum::cases(), 'value')),
            'no_telp' => 'required|string|max:20|unique:pengguna,no_telp',
            'email' => 'nullable|email|max:255|unique:pengguna,email',
            'alamat_domisili' => 'required|string|max:500',
            'alamat_ktp' => 'nullable|string|max:500',
            'pendidikan_terakhir' => 'required|in:' . implode(',', array_column(EducationEnum::cases(), 'value')),
            'nik_ahli_waris' => 'required|digits:16|unique:ahli_waris,nik_ahli_waris',
            'nama_ahli_waris' => 'required|string|max:255',
            'heir_hubungan' => 'required|in:' . implode(',', array_column(AhliWarisEnum::cases(), 'value')),
            'kontak_ahli_waris' => 'required|string|max:20',
            'ktp_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'kk_photo' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
        ];
    }
}
