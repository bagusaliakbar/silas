<?php

namespace Database\Factories;

use App\Models\DigitalCertificate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DigitalCertificate>
 */
class DigitalCertificateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'organization' => fake()->company(),
            'organizational_unit' => 'IT Department',
            'email' => fake()->companyEmail(),
            'country' => 'ID',
            'certificate_path' => 'certificates/cert.crt',
            'private_key_path' => 'certificates/cert.key',
            'p12_path' => null,
            'passphrase' => 'secret123',
            'serial_number' => strtoupper(fake()->hexColor().fake()->hexColor()),
            'issuer' => 'E-Sign Root CA',
            'valid_from' => now()->subDay(),
            'valid_to' => now()->addYear(),
            'is_active' => true,
            'is_default' => false,
        ];
    }
}
