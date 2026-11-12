<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProcessSigningRequest;
use App\Models\Document;
use App\Models\LetterRequest;
use App\Notifications\SystemActivityNotification;
use App\Services\Signers\SignerManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DocumentSigningController extends Controller
{
    public function __construct(
        protected SignerManager $signerManager
    ) {}

    /**
     * Finalize and execute signing for the document.
     */
    public function process(Document $document, ProcessSigningRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user)) {
            abort(403);
        }

        if ($document->isSigned()) {
            return back()->with('error', 'Dokumen ini sudah ditandatangani sebelumnya.');
        }

        $driverName = $request->validated('driver', 'local');
        $certId = $request->validated('digital_certificate_id');

        try {
            $driver = $this->signerManager->driver($driverName);

            $result = $driver->sign($document, [
                'certificate_id' => $certId,
            ]);

            if (! ($result['success'] ?? false)) {
                return back()->with('error', $result['error'] ?? 'Gagal menandatangani dokumen.');
            }

            // Sync letter request status if this document was created from a letter request
            if (! empty($document->metadata['letter_request_id'])) {
                $letterReq = LetterRequest::with('letterType')->find($document->metadata['letter_request_id']);
                if ($letterReq) {
                    $letterReq->update([
                        'status' => 'signed',
                        'signed_at' => now(),
                    ]);

                    if ($letterReq->user && $letterReq->user->id !== $user->id) {
                        $letterTypeName = $letterReq->letterType?->name ?? 'Surat Akademik';

                        $attachmentPath = null;
                        $attachmentName = null;
                        if ($document->signed_file_path) {
                            $fullPath = Storage::disk('local')->path($document->signed_file_path);
                            if (file_exists($fullPath)) {
                                $attachmentPath = $fullPath;
                                $attachmentName = Str::slug($letterTypeName).'-'.($letterReq->npm ?? 'dokumen').'.pdf';
                            }
                        }

                        $letterReq->user->notify(new SystemActivityNotification(
                            title: 'Surat Selesai Ditandatangani (TTE)',
                            message: "Surat {$letterTypeName} Anda telah ditandatangani secara elektronik dan siap diunduh.",
                            url: $letterReq->user->isMahasiswa() ? route('mahasiswa.letters.index') : route('letters.show', $letterReq->uuid),
                            category: 'letter_signed',
                            icon: 'check-circle',
                            attachmentPath: $attachmentPath,
                            attachmentName: $attachmentName,
                            letter: $letterReq
                        ));
                    }
                }
            }

            // Notify document owner/signer
            $user->notify(new SystemActivityNotification(
                title: 'Dokumen Selesai Ditandatangani',
                message: "Dokumen \"{$document->title}\" berhasil ditandatangani dan disegel secara elektronik.",
                url: route('documents.studio', $document),
                category: 'document_signed',
                icon: 'document-check'
            ));

            return redirect()->route('documents.studio', $document)->with(
                'success',
                'Dokumen berhasil ditandatangani dan disegel secara elektronik!'
            );
        } catch (Throwable $e) {
            report($e);

            return back()->with('error', 'Terjadi kesalahan saat memproses penandatanganan: '.$e->getMessage());
        }
    }
}
