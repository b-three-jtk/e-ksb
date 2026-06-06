<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class NotificationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'periode' => ['nullable', 'string', 'regex:/^\d{4}-\d{2}$/'],
            'notification_type' => ['nullable', 'string', 'in:mandatory_saving,installment'],
            'status' => ['nullable', 'string', 'in:draft,sent,failed'],
            'is_read' => ['nullable', 'in:0,1,true,false'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
