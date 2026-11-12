<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSignatureFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'signer_name' => ['required', 'string', 'max:255'],
            'signer_email' => ['required', 'email', 'max:255'],
            'role' => ['nullable', 'string', 'in:signer,approver,viewer'],
            'page_number' => ['required', 'integer', 'min:1'],
            'pos_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'pos_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'width' => ['nullable', 'numeric', 'min:2', 'max:100'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'field_type' => ['nullable', 'string', 'in:signature,qr_code,stamp,initial'],
        ];
    }
}
