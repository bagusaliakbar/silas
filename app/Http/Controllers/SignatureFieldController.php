<?php

namespace App\Http\Controllers;

use App\Http\Requests\SignFieldRequest;
use App\Http\Requests\StoreSignatureFieldRequest;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\SignatureField;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SignatureFieldController extends Controller
{
    /**
     * Add a signature or QR code field to a document.
     */
    public function store(Document $document, StoreSignatureFieldRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user)) {
            abort(403);
        }

        if ($document->isSigned()) {
            return back()->with('error', 'Dokumen yang telah ditandatangani tidak dapat diubah.');
        }

        $validated = $request->validated();
        $fieldType = $validated['field_type'] ?? 'signature';
        $isQr = $fieldType === 'qr_code';
        $isStamp = $fieldType === 'stamp';

        $field = $document->signatureFields()->create([
            'user_id' => $user->id,
            'signer_name' => $isStamp ? 'Stempel Resmi Fakultas' : $validated['signer_name'],
            'signer_email' => $isStamp ? 'fasilkom@unsub.ac.id' : $validated['signer_email'],
            'role' => $isStamp ? 'stamp' : ($validated['role'] ?? 'signer'),
            'page_number' => $validated['page_number'],
            'pos_x' => $validated['pos_x'],
            'pos_y' => $validated['pos_y'],
            'width' => $validated['width'] ?? ($isQr ? 16 : ($isStamp ? 14 : 28)),
            'height' => $validated['height'] ?? ($isQr ? 16 : ($isStamp ? 14 : 10)),
            'field_type' => $fieldType,
            'status' => ($isQr || $isStamp) ? 'signed' : 'pending',
            'signed_at' => ($isQr || $isStamp) ? now() : null,
        ]);

        AuditLog::record(
            $document,
            'field_added',
            $isQr
                ? 'Bidang QR Code Verifikasi ditambahkan pada halaman '.$field->page_number
                : ($isStamp
                    ? 'Bidang Stempel Resmi Fakultas ditambahkan pada halaman '.$field->page_number
                    : 'Bidang tanda tangan ditambahkan pada halaman '.$field->page_number.' untuk '.$field->signer_name),
            [
                'field_id' => $field->id,
                'page' => $field->page_number,
                'type' => $field->field_type,
            ],
            $user
        );

        return back()->with('success', $isQr ? 'Area QR Code verifikasi berhasil ditambahkan.' : 'Area tanda tangan berhasil ditambahkan.');
    }

    /**
     * Update field position and sizing.
     */
    public function update(Document $document, SignatureField $field, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user) || $field->document_id !== $document->id) {
            abort(403);
        }

        if ($document->isSigned()) {
            return back()->with('error', 'Dokumen telah difinalisasi.');
        }

        $validated = $request->validate([
            'pos_x' => ['required', 'numeric', 'min:0', 'max:100'],
            'pos_y' => ['required', 'numeric', 'min:0', 'max:100'],
            'width' => ['nullable', 'numeric', 'min:2', 'max:100'],
            'height' => ['nullable', 'numeric', 'min:1', 'max:100'],
        ]);

        $field->update($validated);

        return back()->with('success', 'Posisi area diperbarui.');
    }

    /**
     * Submit signature data (canvas drawing, typed font, image) for a specific field.
     */
    public function sign(Document $document, SignatureField $field, SignFieldRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user) || $field->document_id !== $document->id) {
            abort(403);
        }

        if ($document->isSigned()) {
            return back()->with('error', 'Dokumen telah difinalisasi.');
        }

        $validated = $request->validated();

        $field->update([
            'signature_data' => $validated['signature_data'],
            'signature_type' => $validated['signature_type'],
            'status' => 'signed',
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'signed_at' => now(),
        ]);

        AuditLog::record(
            $document,
            'field_signed',
            'Tanda tangan visual ('.$validated['signature_type'].') dibubuhkan oleh '.$field->signer_name,
            [
                'field_id' => $field->id,
                'type' => $validated['signature_type'],
            ],
            $user
        );

        return back()->with('success', 'Tanda tangan berhasil dibubuhkan pada area dokumen.');
    }

    /**
     * Delete a signature field.
     */
    public function destroy(Document $document, SignatureField $field, Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user) || $field->document_id !== $document->id) {
            abort(403);
        }

        if ($document->isSigned()) {
            return back()->with('error', 'Dokumen telah difinalisasi.');
        }

        $field->delete();

        AuditLog::record(
            $document,
            'field_removed',
            'Bidang tanda tangan dihapus dari halaman '.$field->page_number,
            ['field_id' => $field->id],
            $user
        );

        return back()->with('success', 'Area tanda tangan dihapus.');
    }
}
