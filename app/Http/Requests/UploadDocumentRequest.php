<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UploadDocumentRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:255'],
            'pdf_file' => ['required', 'file', 'mimes:pdf', 'max:20480'], // max 20MB
            'signing_method' => ['nullable', 'string', 'in:visual_and_qr,cryptographic_pki,third_party_psre'],
            'psre_provider' => ['nullable', 'string', 'in:local,privy,peruri,docusign'],
        ];
    }
}
