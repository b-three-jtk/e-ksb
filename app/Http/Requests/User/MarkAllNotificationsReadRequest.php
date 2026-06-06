<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class MarkAllNotificationsReadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->getRoleNames()->first() === 'Anggota';
    }

    public function rules(): array
    {
        return [];
    }
}
