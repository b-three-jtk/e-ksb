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
            'member_id' => 'required|exists:members,id',
            'saving_account_id' => 'required|exists:saving_accounts,id',
            'amount' => 'required|numeric|min:1',
            'withdrawal_date' => 'required|date|before_or_equal:today',
            'method' => 'required|in:Tunai,Non-Tunai',
            'bank_name' => 'required_if:method,Non-Tunai|nullable|string',
            'account_name' => 'required_if:method,Non-Tunai|nullable|string',
            'account_number' => 'required_if:method,Non-Tunai|nullable|string',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
