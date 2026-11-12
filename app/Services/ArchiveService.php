<?php

namespace App\Services;

use App\Models\Archive;
use App\Models\ArchiveCategory;
use App\Models\IncomingLetter;
use App\Models\LetterRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

class ArchiveService
{
    /**
     * Resolve appropriate ArchiveCategory for a LetterRequest.
     */
    public function resolveCategoryForLetterRequest(LetterRequest $letter): ?ArchiveCategory
    {
        $code = strtolower($letter->letterType?->code ?? '');

        // 1. Surat Keputusan (SK)
        if (str_starts_with($code, 'sk_')) {
            return ArchiveCategory::where('code', 'SK')->first();
        }

        // 2. Nota Kesepahaman (MoU)
        if (str_starts_with($code, 'mou_')) {
            return ArchiveCategory::where('code', 'MOU_KERJASAMA')->first();
        }

        // 3. Surat Tugas
        if (str_starts_with($code, 'tugas_')) {
            return ArchiveCategory::where('code', 'SURAT_TUGAS')->first();
        }

        // 4. Surat Undangan
        if (str_starts_with($code, 'undangan_')) {
            return ArchiveCategory::where('code', 'UNDANGAN')->first();
        }

        // 5. Surat Pengumuman
        if (str_starts_with($code, 'pengumuman_')) {
            return ArchiveCategory::where('code', 'SURAT_PENGUMUMAN')->first();
        }

        // 6. Surat Pengantar & Observasi & Izin
        if (str_starts_with($code, 'pengantar_') || str_starts_with($code, 'observasi_') || str_starts_with($code, 'izin_')) {
            return ArchiveCategory::where('code', 'SURAT_PENGANTAR')->first();
        }

        // 7. Surat Pernyataan / Komitmen
        if (in_array($code, ['kesanggupan_mengajar', 'pakta_integritas'])) {
            return ArchiveCategory::where('code', 'SURAT_PERNYATAAN')->first();
        }

        // 8. Surat Keterangan (Aktif Kuliah, Lulus / Yudisium, dll.)
        if (str_starts_with($code, 'keterangan_')) {
            return ArchiveCategory::where('code', 'SURAT_KETERANGAN')->first();
        }

        // 9. Surat Dinas & Rekomendasi
        return ArchiveCategory::where('code', 'SURAT_DINAS')->first()
            ?? ArchiveCategory::whereIn('letter_direction', ['outgoing', 'both'])->first();
    }

    /**
     * Resolve appropriate ArchiveCategory for an IncomingLetter.
     */
    public function resolveCategoryForIncomingLetter(IncomingLetter $incomingLetter): ?ArchiveCategory
    {
        return ArchiveCategory::where('code', 'MASUK_DINAS')->first()
            ?? ArchiveCategory::whereIn('letter_direction', ['incoming', 'both'])->first();
    }

    /**
     * Automatically archive a LetterRequest once signed / completed.
     */
    public function archiveLetterRequest(LetterRequest $letter, ?User $actor = null): ?Archive
    {
        // Hanya arsipkan surat yang sudah berstatus resmi diterbitkan / ditandatangani
        if ($letter->status !== 'signed') {
            return null;
        }

        $letter->loadMissing(['letterType', 'document', 'user']);

        $category = $this->resolveCategoryForLetterRequest($letter);
        $code = strtolower($letter->letterType?->code ?? '');

        // Judul arsip yang rapi dan informatif
        if (str_starts_with($code, 'sk_')) {
            $extra = $letter->extra_data ?? [];
            $about = ! empty($extra['about_title']) ? strtok($extra['about_title'], "\n") : ($letter->letterType?->name ?? 'Surat Keputusan');
            $title = "SK Dekan: {$about}";
        } elseif (str_starts_with($code, 'mou_')) {
            $extra = $letter->extra_data ?? [];
            $partner = $extra['partner_name'] ?? $letter->student_name;
            $title = "Nota Kesepahaman (MoU) - {$partner}";
        } else {
            $typeName = $letter->letterType?->name ?? 'Surat Keluar';
            $target = $letter->student_name ?: ($letter->user?->name ?? 'Fakultas');
            $npm = $letter->npm ? " ({$letter->npm})" : '';
            $title = "{$typeName} - {$target}{$npm}";
        }

        $sender = $letter->signer_name ?: 'Dekan / Pimpinan Fasilkom UNSUB';
        $recipient = $letter->student_name ? "{$letter->student_name}".($letter->npm ? " ({$letter->npm})" : '') : 'Fakultas Ilmu Komputer';

        // Tentukan file fisik yang valid untuk arsip
        $filePath = null;
        $fileName = null;
        $fileSize = null;

        if ($letter->manual_signed_file_path && Storage::disk('local')->exists($letter->manual_signed_file_path)) {
            $filePath = $letter->manual_signed_file_path;
            $fileName = basename($filePath);
            $fileSize = Storage::disk('local')->size($filePath);
        } elseif ($letter->document?->signed_file_path && Storage::disk('local')->exists($letter->document->signed_file_path)) {
            $filePath = $letter->document->signed_file_path;
            $fileName = basename($filePath);
            $fileSize = Storage::disk('local')->size($filePath);
        } elseif ($letter->document?->file_path && Storage::disk('local')->exists($letter->document->file_path)) {
            $filePath = $letter->document->file_path;
            $fileName = basename($filePath);
            $fileSize = Storage::disk('local')->size($filePath);
        }

        $letterDate = $letter->signed_at
            ? $letter->signed_at->format('Y-m-d')
            : ($letter->created_at ? $letter->created_at->format('Y-m-d') : now()->format('Y-m-d'));

        $refNumber = $letter->letter_number ?: 'Tanpa Nomor Surat';

        return Archive::updateOrCreate(
            ['letter_request_id' => $letter->id],
            [
                'title' => $title,
                'reference_number' => $refNumber,
                'letter_date' => $letterDate,
                'letter_type' => 'keluar',
                'archive_category_id' => $category?->id,
                'sender' => $sender,
                'recipient' => $recipient,
                'drive_url' => $letter->manual_drive_link,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size' => $fileSize,
                'description' => $letter->purpose ?: 'Pengarsipan otomatis dari Surat Keluar Sistem SILAS.',
                'created_by' => $actor?->id ?? $letter->user_id ?? 1,
            ]
        );
    }

    /**
     * Automatically archive an IncomingLetter upon creation or update.
     */
    public function archiveIncomingLetter(IncomingLetter $incomingLetter, ?User $actor = null): ?Archive
    {
        $category = $this->resolveCategoryForIncomingLetter($incomingLetter);

        $letterDate = $incomingLetter->letter_date
            ? $incomingLetter->letter_date->format('Y-m-d')
            : ($incomingLetter->received_date ? $incomingLetter->received_date->format('Y-m-d') : now()->format('Y-m-d'));

        return Archive::updateOrCreate(
            ['incoming_letter_id' => $incomingLetter->id],
            [
                'title' => $incomingLetter->subject,
                'reference_number' => $incomingLetter->reference_number ?: 'Tanpa Nomor',
                'letter_date' => $letterDate,
                'letter_type' => 'masuk',
                'archive_category_id' => $category?->id,
                'sender' => $incomingLetter->sender,
                'recipient' => 'Dekan / Fakultas Ilmu Komputer UNSUB',
                'drive_url' => $incomingLetter->drive_url,
                'file_path' => $incomingLetter->file_path,
                'file_name' => $incomingLetter->file_name,
                'file_size' => $incomingLetter->file_size,
                'description' => $incomingLetter->description ?: 'Pengarsipan otomatis dari agenda Surat Masuk.',
                'created_by' => $actor?->id ?? $incomingLetter->created_by ?? 1,
            ]
        );
    }

    /**
     * Retroactively sync all existing signed letters and incoming letters.
     */
    public function syncAllExisting(): array
    {
        $syncedOutgoing = 0;
        $syncedIncoming = 0;

        $signedLetters = LetterRequest::where('status', 'signed')->get();
        foreach ($signedLetters as $letter) {
            $this->archiveLetterRequest($letter);
            $syncedOutgoing++;
        }

        $incomingLetters = IncomingLetter::all();
        foreach ($incomingLetters as $inc) {
            $this->archiveIncomingLetter($inc);
            $syncedIncoming++;
        }

        return [
            'outgoing' => $syncedOutgoing,
            'incoming' => $syncedIncoming,
        ];
    }
}

