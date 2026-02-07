<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateResignRequest extends FormRequest
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
            'document' => 'required|file|mimes:pdf,doc,docx|max:2048',
        ];
    }

    public function messages(): array
    {
        return [
            'document.mimes' => 'File harus berupa PDF atau DOC',
            'document.max' => 'Ukuran file maksimal 2 MB',
        ];
    }
}
