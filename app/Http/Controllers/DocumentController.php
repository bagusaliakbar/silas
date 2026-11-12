<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadDocumentRequest;
use App\Models\AuditLog;
use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\SigningOfficial;
use App\Notifications\SystemActivityNotification;
use App\Services\PdfSignerService;
use App\Services\QrCodeService;
use App\Services\Signers\SignerManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DocumentController extends Controller
{
    public function __construct(
        protected PdfSignerService $pdfSignerService,
        protected SignerManager $signerManager
    ) {}

    /**
     * Display a listing of documents.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $user->isAdmin()
            ? Document::query()->with(['digitalCertificate', 'signatureFields'])
            : $user->documents()->with(['digitalCertificate', 'signatureFields']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('title', 'like', "%{$search}%");
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $documents = $query->latest()->paginate(10)->withQueryString();

        $statsQuery = $user->isAdmin() ? Document::query() : $user->documents();

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'signed' => (clone $statsQuery)->where('status', 'signed')->count(),
            'draft' => (clone $statsQuery)->where('status', 'draft')->count(),
            'certificates_count' => $user->digitalCertificates()->where('is_active', true)->count(),
        ];

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show form to upload new document.
     */
    public function create(): Response
    {
        return Inertia::render('Documents/Create');
    }

    /**
     * Store uploaded PDF document.
     */
    public function store(UploadDocumentRequest $request): RedirectResponse
    {
        $user = $request->user();
        $file = $request->file('pdf_file');

        $sha256 = hash_file('sha256', $file->getRealPath());

        $directory = 'documents/'.$user->id;
        Storage::disk('local')->makeDirectory($directory);

        $savedFileName = Str::uuid().'_'.time().'.pdf';
        $relativePath = $file->storeAs($directory, $savedFileName, 'local');
        $absolutePath = Storage::disk('local')->path($relativePath);

        $pageCount = $this->pdfSignerService->getPageCount($absolutePath);

        $document = Document::create([
            'user_id' => $user->id,
            'title' => $request->validated('title'),
            'original_filename' => $file->getClientOriginalName(),
            'file_path' => $relativePath,
            'file_size' => $file->getSize(),
            'total_pages' => max(1, $pageCount),
            'sha256_original' => $sha256,
            'status' => 'draft',
            'signing_method' => $request->validated('signing_method', 'visual_and_qr'),
            'psre_provider' => $request->validated('psre_provider', 'local'),
            'metadata' => [
                'uploader_ip' => $request->ip(),
                'original_mime' => $file->getMimeType(),
            ],
        ]);

        AuditLog::record(
            $document,
            'uploaded',
            'Dokumen PDF "'.$document->title.'" berhasil diunggah dengan sidik jari SHA-256: '.substr($sha256, 0, 16).'...',
            [
                'filename' => $document->original_filename,
                'sha256' => $sha256,
                'pages' => $document->total_pages,
            ],
            $user
        );

        $user->notify(new SystemActivityNotification(
            title: 'Dokumen Berhasil Diunggah',
            message: "Dokumen \"{$document->title}\" siap ditandatangani di E-Sign Studio.",
            url: route('documents.studio', $document),
            category: 'document_uploaded',
            icon: 'upload'
        ));

        return redirect()->route('documents.studio', $document)->with('success', 'Dokumen berhasil diunggah.');
    }

    /**
     * The Interactive E-Sign Studio for placing signatures and viewing PDF.
     */
    public function studio(Document $document, Request $request): Response
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user)) {
            abort(403, 'Akses tidak diizinkan ke dokumen ini.');
        }

        $document->load([
            'signatureFields',
            'digitalCertificate',
            'auditLogs' => fn ($q) => $q->latest()->take(20),
        ]);

        $certificates = $user->digitalCertificates()
            ->where('is_active', true)
            ->get(['id', 'name', 'organization', 'serial_number', 'issuer', 'valid_to', 'is_default']);

        $drivers = $this->signerManager->getAvailableDrivers();

        $targetRelativePath = ($document->isSigned() && $document->signed_file_path)
            ? $document->signed_file_path
            : $document->file_path;

        $path = Storage::disk('local')->path($targetRelativePath);
        $pdfBase64 = file_exists($path) ? base64_encode(file_get_contents($path)) : null;

        $verificationUrl = route('documents.verify', ['token' => $document->verification_token]);
        $qrDataUri = app(QrCodeService::class)->generateDataUri($verificationUrl, 240);

        $backUrl = route('documents.index');
        if (! empty($document->metadata['letter_request_id'])) {
            $letterReq = LetterRequest::find($document->metadata['letter_request_id']);
            if ($letterReq) {
                $backUrl = route('letters.show', $letterReq->uuid);
            }
        }

        $signingOfficials = SigningOfficial::query()
            ->active()
            ->ordered()
            ->get(['id', 'code', 'name', 'display_name', 'email', 'nip', 'role_title', 'signature_title', 'signature_image_path']);

        return Inertia::render('Documents/Studio', [
            'document' => $document,
            'pdfUrl' => route('documents.stream', $document, false),
            'pdfBase64' => $pdfBase64,
            'certificates' => $certificates,
            'availableDrivers' => $drivers,
            'signingOfficials' => $signingOfficials,
            'verificationUrl' => $verificationUrl,
            'qrDataUri' => $qrDataUri,
            'backUrl' => $backUrl,
        ]);
    }

    /**
     * Secure stream endpoint for PDF viewing in frontend PDF.js.
     */
    public function streamPdf(Document $document, Request $request): \Illuminate\Http\Response
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user)) {
            abort(403);
        }

        $targetRelativePath = ($document->isSigned() && $document->signed_file_path)
            ? $document->signed_file_path
            : $document->file_path;

        $path = Storage::disk('local')->path($targetRelativePath);

        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        $content = file_get_contents($path);

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($content),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Preview endpoint that dynamically renders all current fields on the draft PDF.
     */
    public function previewPdf(Document $document, Request $request): \Illuminate\Http\Response
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user)) {
            abort(403);
        }

        // If already signed, stream the signed file directly
        if ($document->isSigned() && $document->signed_file_path) {
            return $this->streamPdf($document, $request);
        }

        $pdfContent = $this->pdfSignerService->generatePreviewPdf($document);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Length' => (string) strlen($pdfContent),
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    /**
     * Download signed or original PDF.
     */
    public function download(Document $document, Request $request): BinaryFileResponse
    {
        $user = $request->user();
        if (! $document->isAccessibleBy($user)) {
            abort(403);
        }

        $type = $request->query('type', 'signed');
        $relativePath = ($type === 'signed' && $document->signed_file_path)
            ? $document->signed_file_path
            : $document->file_path;

        $path = Storage::disk('local')->path($relativePath);

        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        AuditLog::record(
            $document,
            'downloaded',
            'File '.($type === 'signed' ? 'bertanda tangan' : 'asli').' diunduh oleh '.$user->name,
            ['type' => $type],
            $user
        );

        $downloadName = ($type === 'signed' ? 'SIGNED_' : 'ORIGINAL_').$document->original_filename;

        return response()->download($path, $downloadName);
    }

    /**
     * Delete document and related files.
     */
    public function destroy(Document $document, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($document->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403);
        }

        if ($document->file_path && Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        if ($document->signed_file_path && Storage::disk('local')->exists($document->signed_file_path)) {
            Storage::disk('local')->delete($document->signed_file_path);
        }

        $document->delete();

        return redirect()->route('documents.index')->with('success', 'Dokumen berhasil dihapus.');
    }
}
