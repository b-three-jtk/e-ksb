<?php

namespace App\Http\Requests;

use App\Enums\UserStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMemberAllocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pj_user_id' => ['required', 'uuid', 'exists:users,id'],
            'member_ids' => ['required', 'array', 'min:1'],
            'member_ids.*' => [
                'required',
                'integer',
                Rule::exists('members', 'id')->where(function ($query) {
                    $query->whereIn('user_id', function ($userQuery) {
                        $userQuery->select('id')
                            ->from('users')
                            ->where('status', UserStatusEnum::ACTIVE->value);
                    });
                }),
            ],
        ];
    }
}
