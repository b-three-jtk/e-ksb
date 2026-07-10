<?php

namespace App\Http\Requests;

use App\Enums\SavingTypeEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDepositRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'anggota_id' => 'required|exists:anggota,id',
            'akun_simpanan_id' => 'nullable|exists:akun_simpanan,id',
            'saving_category' => 'required|in:'. implode(',', array_column(SavingTypeEnum::cases(), 'value')),
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date|before_or_equal:today',
            'metode_pembayaran_simpanan' => 'required|in:Tunai,Non-Tunai',
            'catatan' => 'nullable|string|max:255',
            'no_rekening' => 'nullable|string',
            'nama_bank' => 'nullable|string',
            'atas_nama' => 'nullable|string',

            'bukti_penyetoran' => [
                Rule::requiredIf(
                    $this->metode_pembayaran_simpanan === 'Non-Tunai'
                ),
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:2048',
            ],
            'tujuan' => [
                'required_if:saving_category,Tabungan Ibadah,Tabungan Berjangka',
                'string',
                'max:255',
            ],
            'tenor_months' => 'nullable|integer|min:1|max:360',
            'target_tabungan' => 'nullable|numeric|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'bukti_penyetoran.max' => 'Ukuran bukti penyetoran maksimal 2MB.',
            'bukti_penyetoran.mimes' => 'Bukti penyetoran harus berformat JPG, PNG, atau PDF.',
            'bukti_penyetoran.required' => 'Bukti penyetoran wajib diunggah untuk metode Non-Tunai.',
        ];
    }
}
