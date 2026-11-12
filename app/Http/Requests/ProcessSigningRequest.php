<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProcessSigningRequest extends FormRequest
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
            'driver' => ['required', 'string', 'in:local,privy,peruri,docusign'],
            'digital_certificate_id' => ['nullable', 'integer', 'exists:digital_certificates,id'],
        ];
    }
}
