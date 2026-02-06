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
            'loan_id' => 'required|exists:loans,id',
            'method' => 'required|in:Tunai,Non-Tunai',
            'repayment_total' => 'required|numeric|min:0',
            'principal_paid' => 'required|numeric|min:0',
            'margin_paid' => 'required|numeric|min:0',
            'installment_number' => 'required|integer|min:1',
        ];
    }
}
