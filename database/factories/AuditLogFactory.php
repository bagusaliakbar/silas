<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
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
            'action' => 'uploaded',
            'description' => 'Document uploaded by author',
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
            'payload' => null,
        ];
    }
}
