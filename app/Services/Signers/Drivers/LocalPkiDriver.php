<?php

namespace App\Services\Signers\Drivers;

use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Services\PdfSignerService;
use App\Services\Signers\Contracts\SignerDriverInterface;

class LocalPkiDriver implements SignerDriverInterface
{
    public function __construct(
        protected PdfSignerService $pdfSignerService
    ) {}

    public function getIdentifier(): string
    {
        return 'local';
    }

    public function getDisplayName(): string
    {
        return 'Local PKI & Visual E-Sign';
    }

    public function sign(Document $document, array $options = []): array
    {
        $certificateId = $options['certificate_id'] ?? null;
        $certificate = $certificateId ? DigitalCertificate::find($certificateId) : null;

        $signedPath = $this->pdfSignerService->signDocument($document, $certificate);

        return [
            'success' => true,
            'signed_file_path' => $signedPath,
            'sha256_signed' => $document->fresh()->sha256_signed,
            'metadata' => [
                'provider' => 'local',
                'signed_with_certificate' => (bool) $certificate,
                'certificate_name' => $certificate?->name,
                'signed_at' => now()->toIso8601String(),
            ],
        ];
    }

    public function verify(Document $document): array
    {
        $isValid = $document->isSigned() && ! empty($document->signed_file_path);

        return [
            'is_valid' => $isValid,
            'provider' => 'local',
            'details' => [
                'has_certificate' => (bool) $document->digital_certificate_id,
                'certificate_issuer' => $document->digitalCertificate?->issuer,
                'signed_at' => $document->signed_at?->toIso8601String(),
            ],
        ];
    }
}
