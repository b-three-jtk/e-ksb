<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRepaymentRequest extends FormRequest
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
            'method' => 'required|in:Tunai,Non-Tunai',
            'angsuran_id' => 'required|exists:angsuran,id',
            'no_rekening' => 'exclude_unless:method,Non-Tunai|required|string|max:20|exists:rekening_anggota,no_rekening',
            'bukti_pembayaran' => 'exclude_unless:method,Non-Tunai|required|file|mimes:jpeg,png,jpg,pdf|max:2048',
        ];
    }
}
