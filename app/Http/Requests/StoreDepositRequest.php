<?php

namespace App\Http\Requests;

use App\Enums\SavingTypeEnum;
use Illuminate\Foundation\Http\FormRequest;

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
            'user_id' => 'required|exists:users,id',
            'saving_category' => 'required|string|in:' . implode(',', array_column(SavingTypeEnum::cases(), 'value')),
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date|before_or_equal:today',
            'saving_payment_method' => 'required|in:Tunai,Non-Tunai',
            'notes' => 'nullable|string|max:500',
            'tenor_months' => 'nullable|integer|min:1',
            'target_amount' => 'nullable|numeric|min:1',

            // non-tunai
            'bank_name' => 'required_if:method,Non-Tunai|string|max:100',
            'account_name' => 'required_if:method,Non-Tunai|string|max:150',
            'account_number' => 'required_if:method,Non-Tunai|string|max:50',
            'payment_proof' => 'required_if:method,Non-Tunai|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];
    }
}
