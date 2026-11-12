<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SignFieldRequest extends FormRequest
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
            'signature_data' => ['required', 'string'],
            'signature_type' => ['required', 'string', 'in:canvas_drawing,image_upload,typed_name,system_qr'],
        ];
    }
}
