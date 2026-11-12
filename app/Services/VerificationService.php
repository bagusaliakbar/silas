<?php

namespace App\Services;

use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\SigningOfficial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class VerificationService
{
    /**
     * Verify a document using its public verification token.
     *
     * @return array{
     *     found: bool,
     *     document?: Document,
     *     is_intact: bool,
     *     status: string,
     *     tamper_detected: bool,
     *     certificate_info?: array<string, mixed>|null,
     *     signer_info?: array<string, mixed>|null,
     *     letter_info?: array<string, mixed>|null,
     *     audit_trail: list<array<string, mixed>>,
     *     message: string
     * }
     */
    public function verifyByToken(string $token): array
    {
        $document = Document::with([
            'user',
            'digitalCertificate',
            'signatureFields',
            'auditLogs',
            'letterRequest.letterType',
            'letterRequest.user',
        ])
            ->where('verification_token', $token)
            ->first();

        if (! $document) {
            return [
                'found' => false,
                'is_intact' => false,
                'status' => 'not_found',
                'tamper_detected' => false,
                'audit_trail' => [],
                'message' => 'Dokumen tidak ditemukan dalam sistem verifikasi.',
            ];
        }

        return $this->evaluateDocumentIntegrity($document);
    }

    /**
     * Verify an uploaded PDF file against stored cryptographic hashes.
     *
     * @return array{
     *     found: bool,
     *     document?: Document,
     *     is_intact: bool,
     *     status: string,
     *     tamper_detected: bool,
     *     certificate_info?: array<string, mixed>|null,
     *     signer_info?: array<string, mixed>|null,
     *     letter_info?: array<string, mixed>|null,
     *     audit_trail: list<array<string, mixed>>,
     *     message: string
     * }
     */
    public function verifyUploadedFile(UploadedFile $file): array
    {
        $hash = hash_file('sha256', $file->getRealPath());

        $document = Document::with([
            'user',
            'digitalCertificate',
            'signatureFields',
            'auditLogs',
            'letterRequest.letterType',
            'letterRequest.user',
        ])
            ->where('sha256_signed', $hash)
            ->orWhere(function ($query) use ($hash) {
                $query->where('sha256_original', $hash)->where('status', 'draft');
            })
            ->first();

        if (! $document) {
            return [
                'found' => false,
                'is_intact' => false,
                'status' => 'not_found',
                'tamper_detected' => true,
                'audit_trail' => [],
                'message' => 'Sidik jari digital (SHA-256) file ini tidak cocok dengan dokumen terdaftar mana pun. Dokumen mungkin telah dimodifikasi atau belum ditandatangani melalui sistem ini.',
            ];
        }

        return $this->evaluateDocumentIntegrity($document);
    }

    /**
     * Evaluate cryptographic integrity and certificate validity of a document.
     *
     * @return array{
     *     found: bool,
     *     document: Document,
     *     is_intact: bool,
     *     status: string,
     *     tamper_detected: bool,
     *     certificate_info: array<string, mixed>|null,
     *     signer_info: array<string, mixed>|null,
     *     letter_info: array<string, mixed>|null,
     *     audit_trail: list<array<string, mixed>>,
     *     message: string
     * }
     */
    protected function evaluateDocumentIntegrity(Document $document): array
    {
        $isIntact = true;
        $tamperDetected = false;

        // If document is signed, verify physical file hash
        if ($document->isSigned() && $document->signed_file_path) {
            $path = Storage::disk('local')->path($document->signed_file_path);
            if (file_exists($path)) {
                $currentHash = hash_file('sha256', $path);
                if ($currentHash !== $document->sha256_signed) {
                    $isIntact = false;
                    $tamperDetected = true;
                }
            } else {
                $isIntact = false;
            }
        }

        $certificateInfo = null;
        if ($document->digitalCertificate) {
            $cert = $document->digitalCertificate;
            $certificateInfo = [
                'name' => $cert->name,
                'organization' => $cert->organization,
                'issuer' => $cert->issuer,
                'serial_number' => $cert->serial_number,
                'valid_from' => $this->formatIndonesianDateTime($cert->valid_from, false),
                'valid_to' => $this->formatIndonesianDateTime($cert->valid_to, false),
                'is_valid_now' => $cert->isValid(),
            ];
        }

        if (isset($document->metadata['cryptographic_signature']) && is_array($document->metadata['cryptographic_signature'])) {
            $crypto = $document->metadata['cryptographic_signature'];
            if (! $certificateInfo) {
                $certificateInfo = [
                    'name' => 'Sertifikat Elektronik SILAS-TTE',
                    'organization' => 'Fakultas Ilmu Komputer Universitas Subang',
                    'issuer' => $crypto['issuer'] ?? 'Otoritas Sertifikasi (CA) Fasilkom UNSUB',
                    'serial_number' => $crypto['serial_number'] ?? null,
                    'valid_from' => null,
                    'valid_to' => null,
                    'is_valid_now' => true,
                ];
            }
            $certificateInfo['algorithm'] = $crypto['algorithm'] ?? 'SHA256withRSA';
            $certificateInfo['signature_hash'] = $crypto['signature_hash'] ?? null;
        }

        // Resolve letter request details
        $letter = $document->letterRequest;
        if (! $letter && ! empty($document->metadata['letter_request_id'])) {
            $letter = LetterRequest::with(['letterType', 'user'])->find($document->metadata['letter_request_id']);
        }

        // Resolve official signer information
        $signerName = $letter?->signer_name ?? $document->metadata['signer_name'] ?? null;
        $signerTitle = $letter?->signer_title ?? $document->metadata['signer_title'] ?? null;

        if (! $signerName) {
            $sigField = $document->signatureFields->firstWhere('role', 'signer') ?? $document->signatureFields->first();
            $signerName = $sigField?->signer_name;
        }

        $official = null;
        if ($signerName || $signerTitle) {
            $officialQuery = SigningOfficial::query();
            if ($signerName) {
                $officialQuery->where(function ($q) use ($signerName) {
                    $q->where('name', 'like', "%{$signerName}%")
                        ->orWhere('display_name', 'like', "%{$signerName}%");
                });
            }
            if ($signerTitle) {
                $officialQuery->orWhere(function ($q) use ($signerTitle) {
                    $q->where('signature_title', 'like', "%{$signerTitle}%")
                        ->orWhere('role_title', 'like', "%{$signerTitle}%");
                });
            }
            $official = $officialQuery->first();
        }

        $sigField = $document->signatureFields->firstWhere('role', 'signer') ?? $document->signatureFields->first();
        $resolvedSignerName = $official?->display_name ?: ($official?->name ?: ($signerName ?: ($document->isSigned() ? 'Pejabat Penandatangan FASILKOM UNSUB' : null)));

        $signerInfo = null;
        if ($resolvedSignerName || $document->isSigned()) {
            $signerInfo = [
                'name' => $resolvedSignerName ?: 'Pejabat Penandatangan Resmi',
                'title' => $official?->role_title ?: ($signerTitle ?: 'Penandatangan Dokumen'),
                'signature_title' => $official?->signature_title ?: ($signerTitle ?: 'Pejabat Penandatangan'),
                'nip' => $official?->nip ?? '-',
                'email' => $official?->email ?: ($sigField?->signer_email ?: 'fasilkom@unsub.ac.id'),
                'description' => $official?->description ?: 'Pejabat Struktural Fakultas Ilmu Komputer Universitas Subang',
                'institution' => 'Fakultas Ilmu Komputer, Universitas Subang',
                'status' => $document->isSigned() ? 'signed' : ($document->status ?: 'draft'),
                'signed_at' => $document->signed_at ? $this->formatIndonesianDateTime($document->signed_at, true) : ($letter?->signed_at ? $this->formatIndonesianDateTime($letter->signed_at, true) : null),
                'is_valid' => $isIntact && $document->isSigned(),
            ];
        }

        $letterInfo = null;
        if ($letter) {
            $letterInfo = [
                'letter_number' => $letter->letter_number ?? $document->metadata['letter_number'] ?? '-',
                'letter_type' => $letter->letterType?->name ?? $document->metadata['letter_type'] ?? 'Surat Akademik',
                'letter_code' => $letter->letterType?->code ?? null,
                'student_name' => $letter->student_name ?: ($letter->user?->name ?: $document->user?->name),
                'npm' => $letter->npm ?: ($document->metadata['npm'] ?? ($letter->user?->npm ?? '-')),
                'study_program' => $letter->study_program ?: 'Sistem Informasi',
                'faculty' => 'Fakultas Ilmu Komputer',
                'institution' => 'Universitas Subang',
                'semester' => $letter->semester,
                'academic_year' => $letter->academic_year,
                'birth_info' => $letter->birth_place && $letter->birth_date ? $letter->birth_place.', '.$this->formatIndonesianDate($letter->birth_date) : null,
                'address' => $letter->address,
                'purpose' => $letter->purpose ?: 'Kelengkapan Berkas Administrasi Akademik',
                'extra_data' => $letter->extra_data,
                'created_at' => $this->formatIndonesianDateTime($letter->created_at, false),
                'signed_at' => $this->formatIndonesianDateTime($letter->signed_at, true),
            ];
        } elseif (! empty($document->metadata['letter_number'])) {
            $letterInfo = [
                'letter_number' => $document->metadata['letter_number'],
                'letter_type' => $document->metadata['letter_type'] ?? 'Surat Akademik',
                'letter_code' => null,
                'student_name' => $document->user?->name ?? 'Mahasiswa',
                'npm' => $document->metadata['npm'] ?? '-',
                'study_program' => 'Sistem Informasi',
                'faculty' => 'Fakultas Ilmu Komputer',
                'institution' => 'Universitas Subang',
                'semester' => null,
                'academic_year' => null,
                'birth_info' => null,
                'address' => null,
                'purpose' => 'Administrasi Akademik',
                'extra_data' => null,
                'created_at' => $this->formatIndonesianDateTime($document->created_at, false),
                'signed_at' => $this->formatIndonesianDateTime($document->signed_at, true),
            ];
        }

        $auditTrail = $document->auditLogs->map(fn ($log) => [
            'action' => $log->action,
            'description' => $log->description,
            'created_at' => $this->formatIndonesianDateTime($log->created_at, false),
            'ip_address' => $log->ip_address,
        ])->values()->all();

        $message = $isIntact
            ? 'Dokumen ASLI dan Terverifikasi secara Elektronik. Tidak ada perubahan pada dokumen sejak ditandatangani.'
            : 'PERINGATAN: Integritas dokumen terganggu atau file telah dimodifikasi setelah proses penandatanganan!';

        return [
            'found' => true,
            'document' => $document,
            'is_intact' => $isIntact,
            'status' => $document->status,
            'tamper_detected' => $tamperDetected,
            'certificate_info' => $certificateInfo,
            'signer_info' => $signerInfo,
            'letter_info' => $letterInfo,
            'audit_trail' => $auditTrail,
            'message' => $message,
        ];
    }

    /**
     * Format Carbon datetime to full Indonesian string (e.g. 01 November 2026, 19:10:53 WIB).
     */
    protected function formatIndonesianDateTime($dateTime, bool $withSeconds = true, bool $withTimezone = true): ?string
    {
        if (! $dateTime) {
            return null;
        }

        if (is_string($dateTime)) {
            $dateTime = Carbon::parse($dateTime);
        }

        $dt = $dateTime->copy()->timezone('Asia/Jakarta');
        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $day = str_pad((string) $dt->day, 2, '0', STR_PAD_LEFT);
        $month = $months[$dt->month] ?? $dt->format('F');
        $year = $dt->year;
        $time = $withSeconds ? $dt->format('H:i:s') : $dt->format('H:i');
        $tz = $withTimezone ? ' WIB' : '';

        return "{$day} {$month} {$year}, {$time}{$tz}";
    }

    /**
     * Format Carbon date to full Indonesian string (e.g. 01 November 2026).
     */
    protected function formatIndonesianDate($dateTime): ?string
    {
        if (! $dateTime) {
            return null;
        }

        if (is_string($dateTime)) {
            $dateTime = Carbon::parse($dateTime);
        }

        $months = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
        ];

        $day = str_pad((string) $dateTime->day, 2, '0', STR_PAD_LEFT);
        $month = $months[$dateTime->month] ?? $dateTime->format('F');
        $year = $dateTime->year;

        return "{$day} {$month} {$year}";
    }
}
