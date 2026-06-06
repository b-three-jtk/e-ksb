<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class MarkNotificationPopupDisplayedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->getRoleNames()->first() === 'Anggota';
    }

    public function rules(): array
    {
        return [
            'notification_ids' => ['required', 'array'],
            'notification_ids.*' => ['required', 'integer', 'exists:notifications,id'],
        ];
    }
}
