<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\SignatureField;
use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use setasign\Fpdi\Fpdi;

class PdfSignerService
{
    /**
     * Get the total page count of a PDF file.
     */
    public function getPageCount(string $absolutePdfPath): int
    {
        $fpdi = new Fpdi;

        return (int) $fpdi->setSourceFile($absolutePdfPath);
    }

    /**
     * Generate an in-memory PDF binary string preview of the document with all
     * signature and QR fields stamped, without persisting or altering document state.
     */
    public function generatePreviewPdf(Document $document): string
    {
        $originalRelativePath = $document->file_path;
        $originalAbsolutePath = Storage::disk('local')->path($originalRelativePath);

        if (! file_exists($originalAbsolutePath)) {
            throw new RuntimeException("Original document file not found at: {$originalAbsolutePath}");
        }

        $pdf = new Fpdi;
        $pdf->SetAutoPageBreak(false);

        $pageCount = $pdf->setSourceFile($originalAbsolutePath);
        $document->loadMissing('signatureFields');
        $fieldsByPage = $document->signatureFields->groupBy('page_number');

        $tempFiles = [];

        try {
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Render signature fields on this page
                if ($fieldsByPage->has($pageNo)) {
                    /** @var Collection<int, SignatureField> $fieldsOnPage */
                    $fieldsOnPage = $fieldsByPage->get($pageNo);

                    foreach ($fieldsOnPage as $field) {
                        $this->renderFieldOnPage($pdf, $field, $size, $document, $tempFiles);
                    }
                }
            }

            return $pdf->Output('S');
        } finally {
            // Clean up any temporary image files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    /**
     * Execute signing and stamping on the PDF.
     */
    public function signDocument(Document $document, ?DigitalCertificate $certificate = null): string
    {
        $originalRelativePath = $document->file_path;
        $originalAbsolutePath = Storage::disk('local')->path($originalRelativePath);

        if (! file_exists($originalAbsolutePath)) {
            throw new RuntimeException("Original document file not found at: {$originalAbsolutePath}");
        }

        // Initialize FPDI (extends FPDF)
        $pdf = new Fpdi;
        $pdf->SetAutoPageBreak(false);

        $pageCount = $pdf->setSourceFile($originalAbsolutePath);
        $document->loadMissing('signatureFields');
        $fieldsByPage = $document->signatureFields->groupBy('page_number');

        $tempFiles = [];

        try {
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $orientation = $size['orientation'] ?? ($size['width'] > $size['height'] ? 'L' : 'P');
                $pdf->AddPage($orientation, [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                // Render signature fields on this page
                if ($fieldsByPage->has($pageNo)) {
                    /** @var Collection<int, SignatureField> $fieldsOnPage */
                    $fieldsOnPage = $fieldsByPage->get($pageNo);

                    foreach ($fieldsOnPage as $field) {
                        $this->renderFieldOnPage($pdf, $field, $size, $document, $tempFiles);
                    }
                }
            }

            // Output the signed PDF to disk
            $signedDir = 'documents/signed';
            Storage::disk('local')->makeDirectory($signedDir);
            $signedFileName = 'signed_'.$document->uuid.'_'.time().'.pdf';
            $signedRelativePath = $signedDir.'/'.$signedFileName;
            $signedAbsolutePath = Storage::disk('local')->path($signedRelativePath);

            $pdf->Output('F', $signedAbsolutePath);

            // If digital certificate is provided, sign cryptographic payload
            $certMeta = null;
            if ($certificate && $certificate->isValid()) {
                $certMeta = $this->applyCryptographicSignature($signedAbsolutePath, $certificate);
            }

            // Calculate SHA-256 of the finalized signed document
            $sha256Signed = hash_file('sha256', $signedAbsolutePath);

            // Update document record
            $metadata = array_merge($document->metadata ?? [], [
                'cryptographic_signature' => $certMeta,
            ]);

            $document->update([
                'signed_file_path' => $signedRelativePath,
                'sha256_signed' => $sha256Signed,
                'status' => 'signed',
                'signed_at' => now(),
                'digital_certificate_id' => $certificate?->id,
                'metadata' => $metadata,
            ]);

            // Mark fields as signed
            foreach ($document->signatureFields as $field) {
                $field->update([
                    'status' => 'signed',
                    'signed_at' => now(),
                ]);
            }

            // Record audit log
            AuditLog::record(
                $document,
                'signed',
                $certificate
                    ? 'Dokumen berhasil ditandatangani secara kriptografis menggunakan sertifikat digital ('.$certificate->name.').'
                    : 'Dokumen berhasil ditandatangani secara elektronik (Visual & QR Code verifikasi).',
                [
                    'sha256_signed' => $sha256Signed,
                    'certificate_id' => $certificate?->id,
                    'certificate_name' => $certificate?->name,
                    'total_fields' => $document->signatureFields->count(),
                ]
            );

            return $signedRelativePath;
        } finally {
            // Clean up temporary image files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    @unlink($tempFile);
                }
            }
        }
    }

    /**
     * Render a single field (signature canvas, QR code, or seal) on the PDF page.
     *
     * @param  array{width: float, height: float}  $pageSize
     * @param  list<string>  $tempFiles
     */
    protected function renderFieldOnPage(
        Fpdi $pdf,
        SignatureField $field,
        array $pageSize,
        Document $document,
        array &$tempFiles
    ): void {
        $x = ($field->pos_x / 100) * $pageSize['width'];
        $y = ($field->pos_y / 100) * $pageSize['height'];
        $w = ($field->width / 100) * $pageSize['width'];
        $h = ($field->height / 100) * $pageSize['height'];

        if ($w <= 0) {
            $w = 40;
        }
        if ($h <= 0) {
            $h = 20;
        }

        $verificationUrl = route('documents.verify', ['token' => $document->verification_token]);

        $subangLogo = public_path('images/logos/universitas-subang.png');
        $defaultLogo = file_exists($subangLogo) ? $subangLogo : public_path('images/logos/tutwuri.png');
        $logoPath = $document->metadata['qr_logo_path'] ?? $defaultLogo;

        if ($field->field_type === 'qr_code') {
            $size = min($w, $h);
            $qrX = $x + (($w - $size) / 2);
            $qrY = $y + (($h - $size) / 2);
            $this->renderVectorQrCode($pdf, $qrX, $qrY, $size, $verificationUrl, $logoPath);

            return;
        }

        // Render standalone stamp / cap field
        if ($field->field_type === 'stamp') {
            $stampPath = public_path('images/stamps/stempel-fasilkom.png');
            if (file_exists($stampPath)) {
                $imgInfo = @getimagesize($stampPath);
                if ($imgInfo && ! empty($imgInfo[0]) && ! empty($imgInfo[1])) {
                    $imgW = (float) $imgInfo[0];
                    $imgH = (float) $imgInfo[1];
                    $scaleRatio = min($w / $imgW, $h / $imgH);
                    $drawW = $imgW * $scaleRatio;
                    $drawH = $imgH * $scaleRatio;
                    $drawX = $x + (($w - $drawW) / 2);
                    $drawY = $y + (($h - $drawH) / 2);

                    $pdf->Image($stampPath, $drawX, $drawY, $drawW, $drawH);
                } else {
                    $pdf->Image($stampPath, $x, $y, $w, $h);
                }
            }

            return;
        }

        // Render signature image if available (canvas base64 or uploaded image)
        if (! empty($field->signature_data) && str_starts_with($field->signature_data, 'data:image')) {
            $tempImgPath = $this->extractBase64ImageToTempFile($field->signature_data);
            $tempFiles[] = $tempImgPath;

            $imgInfo = @getimagesize($tempImgPath);
            if ($imgInfo && ! empty($imgInfo[0]) && ! empty($imgInfo[1])) {
                $imgW = (float) $imgInfo[0];
                $imgH = (float) $imgInfo[1];
                $scaleRatio = min($w / $imgW, $h / $imgH);
                $drawW = $imgW * $scaleRatio;
                $drawH = $imgH * $scaleRatio;
                $drawX = $x + (($w - $drawW) / 2);
                $drawY = $y + (($h - $drawH) / 2);

                $pdf->Image($tempImgPath, $drawX, $drawY, $drawW, $drawH);
            } else {
                $pdf->Image($tempImgPath, $x, $y, $w, $h);
            }

            return;
        }

        // If typed signature name
        if (! empty($field->signature_data)) {
            $pdf->SetFont('Helvetica', 'I', 13);
            $pdf->SetTextColor(24, 43, 73);
            $pdf->SetXY($x, $y);
            $pdf->Cell($w, $h * 0.6, $field->signature_data, 0, 1, 'C');

            $pdf->SetFont('Helvetica', '', 7);
            $pdf->SetTextColor(100, 116, 139);
            $pdf->SetXY($x, $y + ($h * 0.6));
            $pdf->Cell($w, $h * 0.4, 'Digitally signed by '.$field->signer_name, 0, 0, 'C');

            return;
        }

        // Default: Draw elegant signature seal with vector QR code and signer info
        $this->renderSignatureSeal($pdf, $x, $y, $w, $h, $verificationUrl, $field, $logoPath);
    }

    /**
     * Render an official e-signature seal containing QR code and signer verification.
     */
    protected function renderSignatureSeal(
        Fpdi $pdf,
        float $x,
        float $y,
        float $w,
        float $h,
        string $verificationUrl,
        SignatureField $field,
        ?string $logoPath = null
    ): void {
        // Draw subtle border & background box
        $pdf->SetDrawColor(203, 213, 225); // Slate 300
        $pdf->SetFillColor(248, 250, 252); // Slate 50
        $pdf->Rect($x, $y, $w, $h, 'DF');

        // Draw Vector QR Code on the left side
        $qrSize = min($h - 3, 16);
        $qrX = $x + 1.5;
        $qrY = $y + (($h - $qrSize) / 2);
        $this->renderVectorQrCode($pdf, $qrX, $qrY, $qrSize, $verificationUrl, $logoPath);

        // Text details on the right side
        $textX = $qrX + $qrSize + 2;
        $textW = $w - ($qrSize + 4);

        $pdf->SetFont('Helvetica', 'B', 6.5);
        $pdf->SetTextColor(15, 23, 42); // Slate 900
        $pdf->SetXY($textX, $y + 1.5);
        $pdf->Cell($textW, 3, 'TANDA TANGAN ELEKTRONIK', 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 5.5);
        $pdf->SetTextColor(51, 65, 85); // Slate 700
        $pdf->SetXY($textX, $y + 4.5);
        $pdf->Cell($textW, 2.5, 'Oleh: '.Str::limit($field->signer_name, 22), 0, 1, 'L');

        $pdf->SetFont('Helvetica', '', 5);
        $pdf->SetTextColor(100, 116, 139); // Slate 500
        $pdf->SetXY($textX, $y + 7);
        $pdf->Cell($textW, 2, 'Waktu: '.now()->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB', 0, 1, 'L');

        $pdf->SetXY($textX, $y + 9);
        $pdf->Cell($textW, 2, 'Pindai QR untuk verifikasi keaslian', 0, 1, 'L');
    }

    /**
     * Draw a crisp vector QR code onto FPDF canvas using rectangles with center logo.
     */
    protected function renderVectorQrCode(Fpdi $pdf, float $x, float $y, float $size, string $data, ?string $logoPath = null): void
    {
        // Use ErrorCorrectionLevel::H() (High 30% error correction) for reliable scanning with center logo
        $qr = Encoder::encode($data, ErrorCorrectionLevel::H());
        $matrix = $qr->getMatrix();
        $cols = $matrix->getWidth();
        $rows = $matrix->getHeight();

        $cellSize = $size / $cols;

        // Draw white background
        $pdf->SetFillColor(255, 255, 255);
        $pdf->Rect($x, $y, $size, $size, 'F');

        // Draw black matrix cells
        $pdf->SetFillColor(15, 23, 42);
        for ($row = 0; $row < $rows; $row++) {
            for ($col = 0; $col < $cols; $col++) {
                if ($matrix->get($col, $row) === 1) {
                    $pdf->Rect(
                        $x + ($col * $cellSize),
                        $y + ($row * $cellSize),
                        $cellSize,
                        $cellSize,
                        'F'
                    );
                }
            }
        }

        // Draw Center Logo badge
        $subangLogo = public_path('images/logos/universitas-subang.png');
        $defaultLogo = file_exists($subangLogo) ? $subangLogo : public_path('images/logos/tutwuri.png');
        $logo = $logoPath ?: $defaultLogo;

        if ($logo && file_exists($logo)) {
            $logoBoxSize = $size * 0.28;
            $logoBoxX = $x + (($size - $logoBoxSize) / 2);
            $logoBoxY = $y + (($size - $logoBoxSize) / 2);

            // Clean white background pad with fine border for the logo
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetDrawColor(226, 232, 240);
            $pdf->Rect($logoBoxX, $logoBoxY, $logoBoxSize, $logoBoxSize, 'DF');

            // Draw Logo inside the pad
            $innerPadding = $logoBoxSize * 0.08;
            $innerLogoSize = $logoBoxSize - ($innerPadding * 2);
            $pdf->Image($logo, $logoBoxX + $innerPadding, $logoBoxY + $innerPadding, $innerLogoSize, $innerLogoSize);
        }
    }

    /**
     * Decode base64 image string to temporary file.
     */
    protected function extractBase64ImageToTempFile(string $base64String): string
    {
        $parts = explode(',', $base64String);
        $data = count($parts) > 1 ? base64_decode($parts[1]) : base64_decode($parts[0]);

        $tempPath = sys_get_temp_dir().'/sig_'.Str::random(16).'.png';
        file_put_contents($tempPath, $data);

        return $tempPath;
    }

    /**
     * Cryptographically sign the PDF file using the private key and append tamper-evident digital signature.
     *
     * @return array<string, mixed>
     */
    protected function applyCryptographicSignature(string $pdfPath, DigitalCertificate $certificate): array
    {
        $privateKeyPem = Storage::disk('local')->get($certificate->private_key_path);
        $privKeyResource = openssl_pkey_get_private($privateKeyPem, $certificate->passphrase ?? '');

        if (! $privKeyResource) {
            return ['status' => 'failed_to_load_key'];
        }

        $pdfContent = file_get_contents($pdfPath);
        $hash = hash('sha256', $pdfContent, true);

        $signature = '';
        openssl_sign($hash, $signature, $privKeyResource, OPENSSL_ALGO_SHA256);

        $signatureHex = bin2hex($signature);

        // Append cryptographic signature block as a PDF comment metadata block
        $sigBlock = "\n%ESIGN-PKI-SIGNATURE-START\n"
            .'%CERT-ISSUER: '.$certificate->issuer."\n"
            .'%CERT-SERIAL: '.$certificate->serial_number."\n"
            .'%SIGNATURE-RSA2048-SHA256: '.$signatureHex."\n"
            ."%ESIGN-PKI-SIGNATURE-END\n";

        file_put_contents($pdfPath, $pdfContent.$sigBlock);

        return [
            'algorithm' => 'SHA256withRSA',
            'serial_number' => $certificate->serial_number,
            'issuer' => $certificate->issuer,
            'signed_at' => now()->toIso8601String(),
            'signature_hash' => hash('sha256', $signature),
        ];
    }
}
