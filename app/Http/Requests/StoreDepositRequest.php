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
            'member_id' => 'required|exists:members,id',
            'saving_category' => 'required|in:'. implode(',', array_column(SavingTypeEnum::cases(), 'value')),
            'amount' => 'required|numeric|min:1',
            'date' => 'required|date|before_or_equal:today',
            'saving_payment_method' => 'required|in:Tunai,Non-Tunai',
            'notes' => 'nullable|string|max:255',
            'purpose' => 'nullable|string|max:255',
            'tenor_months' => 'nullable|integer|min:1|max:360',
            'target_amount' => 'nullable|numeric|min:0',
        ];
    }
}
