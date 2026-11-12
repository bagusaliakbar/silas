<?php

namespace Database\Factories;

use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) Str::uuid(),
            'user_id' => User::factory(),
            'title' => fake()->sentence(3),
            'original_filename' => fake()->word().'.pdf',
            'file_path' => 'documents/sample.pdf',
            'signed_file_path' => null,
            'file_size' => fake()->numberBetween(50000, 2000000),
            'total_pages' => fake()->numberBetween(1, 5),
            'sha256_original' => hash('sha256', Str::random(32)),
            'sha256_signed' => null,
            'status' => 'draft',
            'signing_method' => 'visual_and_qr',
            'psre_provider' => null,
            'digital_certificate_id' => null,
            'verification_token' => Str::random(32),
            'metadata' => ['notes' => 'Contract agreement'],
            'signed_at' => null,
        ];
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'signed',
            'signed_file_path' => 'documents/signed_sample.pdf',
            'sha256_signed' => hash('sha256', Str::random(32)),
            'signed_at' => now(),
        ]);
    }
}
