<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalRequest extends FormRequest
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
            'akun_simpanan_id' => 'required|exists:akun_simpanan,id',
            'amount' => 'required|numeric|min:1',
            'withdrawal_date' => 'required|date|before_or_equal:today',
            'method' => 'required|in:Tunai,Non-Tunai',
            'nama_bank' => 'required_if:method,Non-Tunai|nullable|string',
            'atas_nama' => 'required_if:method,Non-Tunai|nullable|string',
            'no_rekening' => 'required_if:method,Non-Tunai|nullable|string',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
