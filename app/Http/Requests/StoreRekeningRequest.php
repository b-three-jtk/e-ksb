<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreRekeningRequest extends FormRequest
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
            'no_rekening' => 'required|string|max:20|unique:rekening_anggota,no_rekening',
            'nama_bank' => 'required|string|max:100',
            'atas_nama' => 'required|string|max:255',
            'anggota_id' => 'required|exists:anggota,id',
        ];
    }
}
