<?php

namespace App\Services\Signers\Contracts;

use App\Models\Document;

interface SignerDriverInterface
{
    /**
     * Get provider slug/identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get readable display name of the provider.
     */
    public function getDisplayName(): string;

    /**
     * Execute the signing process for the given document.
     *
     * @param  array<string, mixed>  $options
     * @return array{
     *     success: bool,
     *     signed_file_path?: string,
     *     sha256_signed?: string,
     *     metadata?: array<string, mixed>,
     *     error?: string
     * }
     */
    public function sign(Document $document, array $options = []): array;

    /**
     * Check verification status or certificate status.
     *
     * @return array{
     *     is_valid: bool,
     *     provider: string,
     *     details: array<string, mixed>
     * }
     */
    public function verify(Document $document): array;
}
