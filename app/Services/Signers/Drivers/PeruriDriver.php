<?php

namespace App\Services\Signers\Drivers;

use App\Models\AuditLog;
use App\Models\Document;
use App\Services\PdfSignerService;
use App\Services\Signers\Contracts\SignerDriverInterface;
use Illuminate\Support\Str;

class PeruriDriver implements SignerDriverInterface
{
    public function __construct(
        protected PdfSignerService $pdfSignerService
    ) {}

    public function getIdentifier(): string
    {
        return 'peruri';
    }

    public function getDisplayName(): string
    {
        return 'Peruri (PSrE / E-Meterai BUMN)';
    }

    public function sign(Document $document, array $options = []): array
    {
        $sn = 'PERURI-'.strtoupper(Str::random(14));

        $signedPath = $this->pdfSignerService->signDocument($document);

        $metadata = array_merge($document->metadata ?? [], [
            'psre_provider' => 'peruri',
            'peruri_serial_number' => $sn,
            'peruri_status' => 'SIGNED',
            'certificate_authority' => 'Perum Percetakan Uang RI (Peruri CA)',
            'signed_at' => now()->toIso8601String(),
        ]);

        $document->update([
            'psre_provider' => 'peruri',
            'metadata' => $metadata,
        ]);

        AuditLog::record(
            $document,
            'psre_signed',
            'Dokumen berhasil disertifikasi oleh Peruri Digital Signer (SN: '.$sn.').',
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
            'is_valid' => ($meta['peruri_status'] ?? '') === 'SIGNED',
            'provider' => 'peruri',
            'details' => [
                'serial_number' => $meta['peruri_serial_number'] ?? null,
                'authority' => $meta['certificate_authority'] ?? 'Peruri CA',
                'signed_at' => $meta['signed_at'] ?? null,
            ],
        ];
    }
}
