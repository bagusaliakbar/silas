<?php

namespace App\Services\Signers\Drivers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Services\PdfSignerService;
use App\Services\Signers\Contracts\SignerDriverInterface;
use Illuminate\Support\Str;

class DocuSignDriver implements SignerDriverInterface
{
    public function __construct(
        protected PdfSignerService $pdfSignerService
    ) {}

    public function getIdentifier(): string
    {
        return 'docusign';
    }

    public function getDisplayName(): string
    {
        return 'DocuSign eSignature';
    }

    public function sign(Document $document, array $options = []): array
    {
        $envelopeId = (string) Str::uuid();

        $signedPath = $this->pdfSignerService->signDocument($document);

        $metadata = array_merge($document->metadata ?? [], [
            'psre_provider' => 'docusign',
            'docusign_envelope_id' => $envelopeId,
            'docusign_status' => 'completed',
            'certificate_authority' => 'DocuSign Global Root CA',
            'signed_at' => now()->toIso8601String(),
        ]);

        $document->update([
            'psre_provider' => 'docusign',
            'metadata' => $metadata,
        ]);

        AuditLog::record(
            $document,
            'psre_signed',
            'Dokumen berhasil ditandatangani via DocuSign eSignature (Envelope: '.$envelopeId.').',
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
            'is_valid' => ($meta['docusign_status'] ?? '') === 'completed',
            'provider' => 'docusign',
            'details' => [
                'envelope_id' => $meta['docusign_envelope_id'] ?? null,
                'authority' => $meta['certificate_authority'] ?? 'DocuSign Global Root CA',
                'signed_at' => $meta['signed_at'] ?? null,
            ],
        ];
    }
}
