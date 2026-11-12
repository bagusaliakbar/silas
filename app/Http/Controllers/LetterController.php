<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lecturer;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Notifications\SystemActivityNotification;
use App\Services\AcademicLetterService;
use App\Services\SkService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LetterController extends Controller
{
    public function __construct(
        protected AcademicLetterService $letterService,
        protected SkService $skService
    ) {}

    /**
     * Display a listing of letters.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = LetterRequest::with(['letterType', 'document', 'user', 'archive']);

        $isOfficial = str_contains($user->email, 'admin')
            || str_contains($user->email, 'kaprodi')
            || str_contains($user->email, 'dekan')
            || str_contains($user->email, 'wadek')
            || $user->email === 'signer@example.com';

        if (! $isOfficial) {
            $query->where('user_id', $user->id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                    ->orWhere('npm', 'like', "%{$search}%")
                    ->orWhere('letter_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $letters = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => LetterRequest::count(),
            'submitted' => LetterRequest::where('status', 'submitted')->count(),
            'signed' => LetterRequest::where('status', 'signed')->count(),
            'rejected' => LetterRequest::where('status', 'rejected')->count(),
        ];

        return Inertia::render('Letters/Index', [
            'letters' => $letters,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    /**
     * Show form to request a new letter.
     */
    public function create(Request $request): Response
    {
        $letterTypes = LetterType::where('is_active', true)
            ->where('code', 'not like', 'sk_%')
            ->where('name', 'not like', 'SK %')
            ->where('name', 'not like', 'Surat Keputusan%')
            ->orderBy('order')
            ->get();
        $signers = AcademicLetterService::getAvailableSigners();
        $lecturers = Lecturer::active()->orderBy('name')->get([
            'id', 'name', 'nidn', 'nip', 'functional_position', 'academic_rank', 'study_program'
        ]);
        $courses = Course::active()->orderBy('name')->get([
            'id', 'code', 'name', 'sks', 'semester', 'study_program'
        ]);

        return Inertia::render('Letters/Create', [
            'letterTypes' => $letterTypes,
            'signers' => $signers,
            'lecturers' => $lecturers,
            'courses' => $courses,
            'user' => $request->user(),
        ]);
    }

    /**
     * Store a newly created letter request and generate draft PDF.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'letter_type_id' => ['required', 'exists:letter_types,id'],
            'student_name' => ['required', 'string', 'max:255'],
            'npm' => ['required', 'string', 'max:50'],
            'study_program' => ['required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'semester' => ['required', 'integer', 'min:1', 'max:14'],
            'academic_year' => ['required', 'string', 'max:20'],
            'purpose' => ['nullable', 'string'],
            'signer_id' => ['nullable', 'string', 'max:50'],
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signer_title' => ['nullable', 'string', 'max:255'],
            'extra_data' => ['nullable', 'array'],
        ]);

        $signers = collect(AcademicLetterService::getAvailableSigners())->keyBy('id');
        $selectedSigner = $signers->get($validated['signer_id'] ?? 'kaprodi', $signers->get('kaprodi'));

        $letterType = LetterType::find($validated['letter_type_id']);
        if ($letterType && in_array($letterType->code, ['kesanggupan_mengajar', 'pakta_integritas'])) {
            $extra = $validated['extra_data'] ?? [];
            $signerName = ! empty($extra['lecturer_name']) ? $extra['lecturer_name'] : $validated['student_name'];
            $signerTitle = 'Dosen Pengampu';
        } else {
            $signerName = ! empty($validated['signer_name']) ? $validated['signer_name'] : ($selectedSigner['name'] ?? 'BAGUS ALI AKBAR, S.Si., M.Kom');
            $signerTitle = ! empty($validated['signer_title']) ? $validated['signer_title'] : ($selectedSigner['signature_title'] ?? 'Ka. Program Studi');
        }

        $letter = LetterRequest::create([
            'user_id' => $request->user()->id,
            'letter_type_id' => $validated['letter_type_id'],
            'student_name' => $validated['student_name'],
            'npm' => $validated['npm'],
            'study_program' => $validated['study_program'] ?? 'Sistem Informasi',
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'semester' => $validated['semester'],
            'academic_year' => $validated['academic_year'],
            'purpose' => $validated['purpose'] ?? null,
            'extra_data' => $validated['extra_data'] ?? [],
            'status' => 'submitted',
            'signer_name' => $signerName,
            'signer_title' => $signerTitle,
        ]);

        // Automatically generate standard PDF document
        $this->letterService->generatePdf($letter);

        $letter->load('letterType');
        $letterTypeName = $letter->letterType?->name ?? 'Surat Akademik';

        $request->user()->notify(new SystemActivityNotification(
            title: 'Pengajuan Surat Diterbitkan',
            message: "Permohonan {$letterTypeName} untuk {$letter->student_name} telah dibuat.",
            url: route('letters.show', $letter->uuid),
            category: 'letter_submitted',
            icon: 'document'
        ));

        return redirect()->route('letters.show', $letter->uuid)
            ->with('success', 'Permohonan surat berhasil diajukan dan draf dokumen telah diterbitkan.');
    }

    /**
     * Display letter detail.
     */
    public function show(LetterRequest $letter, Request $request): Response
    {
        $letter->load(['letterType', 'document.signatureFields', 'user']);

        $pdfBase64 = null;
        if ($letter->document) {
            $targetPath = ($letter->document->isSigned() && $letter->document->signed_file_path)
                ? $letter->document->signed_file_path
                : $letter->document->file_path;

            $fullPath = Storage::disk('local')->path($targetPath);
            if (file_exists($fullPath)) {
                $pdfBase64 = base64_encode(file_get_contents($fullPath));
            }
        }

        $signers = AcademicLetterService::getAvailableSigners();

        return Inertia::render('Letters/Show', [
            'letter' => $letter,
            'pdfBase64' => $pdfBase64,
            'signers' => $signers,
            'verificationUrl' => $letter->document ? route('documents.verify', $letter->document->verification_token) : null,
        ]);
    }

    /**
     * Update the designated signer for this letter request and refresh draft PDF.
     */
    public function updateSigner(LetterRequest $letter, Request $request): RedirectResponse
    {
        if ($request->user()->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        if ($letter->isSigned()) {
            return back()->with('error', 'Surat yang sudah ditandatangani tidak dapat diubah penandatangannya.');
        }

        $validated = $request->validate([
            'signer_id' => ['required', 'string', 'max:50'],
        ]);

        $signers = collect(AcademicLetterService::getAvailableSigners(false))->keyBy('id');
        $selectedSigner = $signers->get($validated['signer_id']);

        if (! $selectedSigner) {
            return back()->with('error', 'Pejabat penandatangan tidak valid.');
        }

        $letter->update([
            'signer_name' => $selectedSigner['name'],
            'signer_title' => $selectedSigner['signature_title'],
        ]);

        // Check if letter is SK (Surat Keputusan)
        $isSk = str_starts_with($letter->letterType?->code ?? '', 'sk_');

        if ($isSk) {
            // Re-render SK PDF
            $pdfContent = $this->skService->renderSkPdf($letter, false);
            if ($letter->document && $letter->document->file_path) {
                Storage::disk('local')->put($letter->document->file_path, $pdfContent);
                $fullPath = Storage::disk('local')->path($letter->document->file_path);
                $sha256 = hash('sha256', $pdfContent);

                $letter->document->update([
                    'file_size' => strlen($pdfContent),
                    'sha256_original' => $sha256,
                    'metadata' => array_merge($letter->document->metadata ?? [], [
                        'signer_name' => $selectedSigner['name'],
                        'signer_title' => $selectedSigner['signature_title'],
                    ]),
                ]);

                // Update signature field position & signer info
                $sigField = \App\Models\SignatureField::where('document_id', $letter->document->id)->first();
                if ($sigField) {
                    $sigPage = (int) $this->skService->skSignaturePage;
                    $sigY = $this->skService->skSignatureY;
                    $posYPercent = round((($sigY + 12) / 297) * 100, 1);
                    $signerEmail = $selectedSigner['email'] ?? 'tepipeirisal@unsub.ac.id';

                    $sigField->update([
                        'signer_name' => $selectedSigner['name'],
                        'signer_email' => $signerEmail,
                        'page_number' => $sigPage,
                        'pos_y' => $posYPercent,
                    ]);
                }
            }
        } else {
            // Re-generate draft PDF with updated opening & signature block
            $this->letterService->generatePdf($letter);
        }

        return back()->with('success', 'Pejabat penandatangan berhasil diubah menjadi: '.$selectedSigner['display_name'].' ('.$selectedSigner['signature_title'].'). Draf PDF telah diperbarui.');
    }

    /**
     * Toggle download permission for students.
     */
    public function toggleDownload(LetterRequest $letter, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'can_download_pdf' => ['required', 'boolean'],
            'pickup_note' => ['nullable', 'string', 'max:255'],
        ]);

        $letter->update([
            'can_download_pdf' => $validated['can_download_pdf'],
            'pickup_note' => $validated['pickup_note'] ?? null,
        ]);

        $statusMsg = $letter->can_download_pdf
            ? 'Unduhan PDF mandiri oleh mahasiswa telah diaktifkan.'
            : 'Unduhan PDF dinonaktifkan. Mahasiswa diarahkan untuk mengambil surat cetak fisik di Fakultas.';

        return back()->with('success', $statusMsg);
    }

    /**
     * Upload scanned PDF/image or Google Drive link of physical/manual signed letter and finalize letter.
     */
    public function uploadManualSigned(LetterRequest $letter, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'signed_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // max 10MB
            'drive_link' => ['nullable', 'url', 'max:1000'],
        ]);

        if (! $request->hasFile('signed_file') && empty($validated['drive_link'])) {
            return back()->withErrors([
                'signed_file' => 'Silakan pilih berkas scan untuk diunggah atau masukkan link Google Drive dokumen.',
            ]);
        }

        $storedPath = $letter->manual_signed_file_path;
        $driveLink = $validated['drive_link'] ?? null;

        if ($request->hasFile('signed_file')) {
            $file = $request->file('signed_file');
            $extension = $file->getClientOriginalExtension();
            $filename = 'manual-signed-' . $letter->uuid . '-' . time() . '.' . $extension;
            $storedPath = $file->storeAs('letters/manual_signed', $filename, 'local');
            // Jika admin mengunggah file langsung, reset drive link agar mode file yang aktif
            $driveLink = null;
        } elseif (! empty($driveLink)) {
            // Jika admin memasukkan link Google Drive, reset path file manual agar mode drive yang aktif
            $storedPath = null;
        }

        // Update Document if exists
        if ($letter->document) {
            $letter->document->update([
                'signed_file_path' => $storedPath ?: $letter->document->signed_file_path,
                'status' => 'signed',
                'signed_at' => now(),
                'signing_method' => 'manual_stamp',
            ]);
        }

        $letter->update([
            'status' => 'signed',
            'signing_mode' => 'manual',
            'manual_signed_file_path' => $storedPath,
            'manual_drive_link' => $driveLink,
            'signed_at' => now(),
        ]);

        $letter->refresh();
        $letter->load(['user', 'letterType', 'document']);
        $letterTypeName = $letter->letterType?->name ?? 'Surat Akademik';
        $letterNumber = $letter->letter_number ?? 'Resmi';

        $attachmentPath = null;
        $attachmentName = null;
        if ($letter->manual_signed_file_path) {
            $fullPath = Storage::disk('local')->path($letter->manual_signed_file_path);
            if (file_exists($fullPath)) {
                $attachmentPath = $fullPath;
                $attachmentName = Str::slug($letterTypeName).'-'.($letter->npm ?? 'dokumen').'.pdf';
            }
        }

        // Notify student
        if ($letter->user) {
            $letter->user->notify(new SystemActivityNotification(
                title: 'Surat Selesai Ditandatangani & Dicap Basah',
                message: "Surat {$letterTypeName} ({$letterNumber}) telah selesai ditandatangani manual dan disahkan oleh Fakultas.",
                url: $letter->user->isMahasiswa() ? route('mahasiswa.letters.index') : route('letters.show', $letter->uuid),
                category: 'letter_signed',
                icon: 'check-circle',
                attachmentPath: $attachmentPath,
                attachmentName: $attachmentName,
                letter: $letter
            ));
        }

        return back()->with('success', 'Hasil tanda tangan & cap manual berhasil disimpan. Surat kini berstatus Sah/Selesai.');
    }

    /**
     * Kaprodi one-click approval & TTE digital seal with QR Code.
     */
    public function sign(LetterRequest $letter, Request $request): RedirectResponse
    {
        if ($request->user()->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        if ($letter->isSigned()) {
            return back()->with('error', 'Surat ini telah ditandatangani.');
        }

        $this->letterService->approveAndSeal($letter);
        $letter->refresh();
        $letter->load(['user', 'letterType', 'document']);

        $letterTypeName = $letter->letterType?->name ?? 'Surat Akademik';
        $letterNumber = $letter->letter_number ?? 'Resmi';

        $attachmentPath = null;
        $attachmentName = null;
        if ($letter->document && $letter->document->signed_file_path) {
            $fullPath = Storage::disk('local')->path($letter->document->signed_file_path);
            if (file_exists($fullPath)) {
                $attachmentPath = $fullPath;
                $attachmentName = Str::slug($letterTypeName).'-'.($letter->npm ?? 'dokumen').'.pdf';
            }
        }

        // Notify the student / applicant
        if ($letter->user) {
            $letter->user->notify(new SystemActivityNotification(
                title: 'Surat Selesai Ditandatangani (TTE)',
                message: "Surat {$letterTypeName} ({$letterNumber}) telah ditandatangani secara elektronik (TTE) dan siap diunduh.",
                url: $letter->user->isMahasiswa() ? route('mahasiswa.letters.index') : route('letters.show', $letter->uuid),
                category: 'letter_signed',
                icon: 'check-circle',
                attachmentPath: $attachmentPath,
                attachmentName: $attachmentName,
                letter: $letter
            ));
        }

        // Notify the signer
        $request->user()->notify(new SystemActivityNotification(
            title: 'Surat Berhasil Ditandatangani',
            message: "Anda telah membubuhkan TTE pada {$letterTypeName} ({$letterNumber}) untuk {$letter->student_name}.",
            url: route('letters.show', $letter->uuid),
            category: 'letter_signed',
            icon: 'check-circle',
            letter: $letter
        ));

        return back()->with('success', 'Surat berhasil disetujui dan ditandatangani secara elektronik (TTE) berlogo Universitas Subang.');
    }

    /**
     * Reject letter request.
     */
    public function reject(LetterRequest $letter, Request $request): RedirectResponse
    {
        if ($request->user()->isMahasiswa()) {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:500'],
        ]);

        $letter->update([
            'status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        $letter->load(['user', 'letterType']);
        $letterTypeName = $letter->letterType?->name ?? 'Surat Akademik';

        // Notify student of rejection
        if ($letter->user) {
            $letter->user->notify(new SystemActivityNotification(
                title: 'Permohonan Surat Ditolak',
                message: "Permohonan {$letterTypeName} Anda ditolak. Alasan: {$validated['rejection_reason']}",
                url: $letter->user->isMahasiswa() ? route('mahasiswa.letters.index') : route('letters.show', $letter->uuid),
                category: 'letter_rejected',
                icon: 'x-circle',
                letter: $letter,
                details: [
                    'Alasan Penolakan' => $validated['rejection_reason'],
                ]
            ));
        }

        $request->user()->notify(new SystemActivityNotification(
            title: 'Permohonan Surat Ditolak',
            message: "Anda telah menolak permohonan {$letterTypeName} untuk {$letter->student_name}.",
            url: route('letters.show', $letter->uuid),
            category: 'letter_rejected',
            icon: 'x-circle',
            letter: $letter
        ));

        return back()->with('success', 'Permohonan surat ditolak.');
    }

    /**
     * Delete letter request and its associated files.
     */
    public function destroy(LetterRequest $letter, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa() && $letter->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        $letterTypeName = $letter->letterType?->name ?? 'Surat';
        $letterNumber = $letter->letter_number ?? '';

        // Delete associated Document and files if exists
        if ($letter->document) {
            if ($letter->document->file_path && Storage::disk('local')->exists($letter->document->file_path)) {
                Storage::disk('local')->delete($letter->document->file_path);
            }
            if ($letter->document->signed_file_path && Storage::disk('local')->exists($letter->document->signed_file_path)) {
                Storage::disk('local')->delete($letter->document->signed_file_path);
            }
            $letter->document->delete();
        }

        // Delete associated manual uploaded signed file if exists
        if ($letter->manual_signed_file_path && Storage::disk('local')->exists($letter->manual_signed_file_path)) {
            Storage::disk('local')->delete($letter->manual_signed_file_path);
        }

        // Delete associated archive if any
        if ($letter->archive) {
            $letter->archive->delete();
        }

        $letter->delete();

        return redirect()->route('letters.index')
            ->with('success', "{$letterTypeName} ".($letterNumber ? "({$letterNumber}) " : '')."berhasil dihapus.");
    }

    /**
     * Download the official letter PDF.
     */
    public function download(LetterRequest $letter, Request $request): BinaryFileResponse
    {
        if (! $letter->document) {
            abort(404, 'Dokumen surat belum diterbitkan.');
        }

        $targetPath = ($letter->document->isSigned() && $letter->document->signed_file_path)
            ? $letter->document->signed_file_path
            : $letter->document->file_path;

        $path = Storage::disk('local')->path($targetPath);

        if (! file_exists($path)) {
            abort(404, 'File PDF tidak ditemukan.');
        }

        $filename = 'SURAT_'.Str::slug($letter->letterType->code).'_'.$letter->npm.'.pdf';

        return response()->download($path, $filename);
    }
}
