<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\SignatureField;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SignatureField>
 */
class SignatureFieldFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'document_id' => Document::factory(),
            'user_id' => User::factory(),
            'signer_name' => fake()->name(),
            'signer_email' => fake()->safeEmail(),
            'role' => 'signer',
            'page_number' => 1,
            'pos_x' => 50.0,
            'pos_y' => 70.0,
            'width' => 160.0,
            'height' => 65.0,
            'field_type' => 'signature',
            'signature_type' => null,
            'signature_data' => null,
            'signature_image_path' => null,
            'digital_certificate_id' => null,
            'status' => 'pending',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'signed_at' => null,
        ];
    }
}
