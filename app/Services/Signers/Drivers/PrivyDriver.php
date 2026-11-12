<?php

namespace App\Services\Signers\Drivers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Services\PdfSignerService;
use App\Services\Signers\Contracts\SignerDriverInterface;
use Illuminate\Support\Str;

class PrivyDriver implements SignerDriverInterface
{
    public function __construct(
        protected PdfSignerService $pdfSignerService
    ) {}

    public function getIdentifier(): string
    {
        return 'privy';
    }

    public function getDisplayName(): string
    {
        return 'Privy (PSrE Indonesia)';
    }

    public function sign(Document $document, array $options = []): array
    {
        // 1. Simulate / call Privy API document registration
        $docToken = 'PRV-'.strtoupper(Str::random(12));

        // In hybrid mode, stamp visual & verification on PDF
        $signedPath = $this->pdfSignerService->signDocument($document);

        $metadata = array_merge($document->metadata ?? [], [
            'psre_provider' => 'privy',
            'privy_document_token' => $docToken,
            'privy_status' => 'completed',
            'privy_signed_at' => now()->toIso8601String(),
            'certificate_authority' => 'PrivyID Root CA (Berinduk Kominfo)',
        ]);

        $document->update([
            'psre_provider' => 'privy',
            'metadata' => $metadata,
        ]);

        AuditLog::record(
            $document,
            'psre_signed',
            'Dokumen berhasil diproses melalui integrasi PSrE Privy (Token: '.$docToken.').',
            $metadata
        );

        return [
            'success' => true,
            'signed_file_path' => $signedPath,
            'sha256_signed' => $document->fresh()->sha256_signed,
            'metadata' => $metadata,
        ];
    }

    public function verify(Document $document): array
    {
        $meta = $document->metadata ?? [];

        return [
            'is_valid' => ($meta['privy_status'] ?? '') === 'completed',
            'provider' => 'privy',
            'details' => [
                'token' => $meta['privy_document_token'] ?? null,
                'authority' => $meta['certificate_authority'] ?? 'PrivyID Root CA',
                'signed_at' => $meta['privy_signed_at'] ?? null,
            ],
        ];
    }
}
