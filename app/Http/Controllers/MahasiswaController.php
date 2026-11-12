<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use App\Notifications\SystemActivityNotification;
use App\Services\AcademicLetterService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MahasiswaController extends Controller
{
    public function __construct(
        protected AcademicLetterService $letterService
    ) {}

    /**
     * Display student portal dashboard.
     */
    public function dashboard(Request $request): Response
    {
        $user = $request->user();

        $query = LetterRequest::where('user_id', $user->id)->with(['letterType', 'document']);

        $stats = [
            'total' => (clone $query)->count(),
            'submitted' => (clone $query)->where('status', 'submitted')->count(),
            'signed' => (clone $query)->where('status', 'signed')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
        ];

        $recentLetters = (clone $query)->latest()->take(5)->get();
        $availableTypes = LetterType::where('is_active', true)
            ->where('code', 'not like', 'sk_%')
            ->whereNotIn('code', [
                'tugas_mengajar',
                'tugas_pembimbing_mbkm',
                'tugas_pembimbing_ta',
                'undangan_rapat_akademik',
                'pengantar_magang',
                'pengantar_pkl',
                'keterangan_yudisium',
                'kesanggupan_mengajar',
                'pakta_integritas',
                'pengajuan_dana',
                'pengadaan_sarpras',
                'perbaikan_sarpras',
                'pemberitahuan_ujian_dosen',
                'pengumuman_ujian_mahasiswa',
                'perbaikan_data_pddikti',
                'sk_dekan_pembimbing_mbkm',
                'pembukaan_periode',
                'pengajuan_ttd_ijazah',
                'pengajuan_blanko_ijazah_transkrip',
                'rekomendasi_pinjaman_bjb',
                'mou_magang_mbkm',
            ])
            ->orderBy('order')
            ->get();

        return Inertia::render('Mahasiswa/Dashboard', [
            'user' => $user,
            'stats' => $stats,
            'recentLetters' => $recentLetters,
            'availableTypes' => $availableTypes,
        ]);
    }

    /**
     * Show form for requesting a new academic letter.
     */
    public function create(Request $request): Response
    {
        $letterTypes = LetterType::where('is_active', true)
            ->where('code', 'not like', 'sk_%')
            ->whereNotIn('code', [
                'tugas_mengajar',
                'tugas_pembimbing_mbkm',
                'tugas_pembimbing_ta',
                'undangan_rapat_akademik',
                'pengantar_magang',
                'pengantar_pkl',
                'keterangan_yudisium',
                'kesanggupan_mengajar',
                'pakta_integritas',
                'pengajuan_dana',
                'pengadaan_sarpras',
                'perbaikan_sarpras',
                'pemberitahuan_ujian_dosen',
                'pengumuman_ujian_mahasiswa',
                'perbaikan_data_pddikti',
                'sk_dekan_pembimbing_mbkm',
                'pembukaan_periode',
                'pengajuan_ttd_ijazah',
                'pengajuan_blanko_ijazah_transkrip',
                'rekomendasi_pinjaman_bjb',
                'mou_magang_mbkm',
            ])
            ->orderBy('order')
            ->get();
        $signers = AcademicLetterService::getAvailableSigners();

        $courses = Course::active()->orderBy('name')->get([
            'id', 'code', 'name', 'sks', 'semester', 'study_program',
        ]);

        return Inertia::render('Mahasiswa/Pengajuan', [
            'letterTypes' => $letterTypes,
            'signers' => $signers,
            'courses' => $courses,
            'user' => $request->user(),
            'initialType' => $request->query('type'),
        ]);
    }

    /**
     * Store new student letter application.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

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
            'extra_data' => ['nullable', 'array'],
        ]);

        $letterType = LetterType::findOrFail($validated['letter_type_id']);
        if (str_starts_with($letterType->code, 'sk_') || in_array($letterType->code, [
            'tugas_mengajar',
            'tugas_pembimbing_mbkm',
            'tugas_pembimbing_ta',
            'undangan_rapat_akademik',
            'pengantar_magang',
            'pengantar_pkl',
            'keterangan_yudisium',
            'kesanggupan_mengajar',
            'pakta_integritas',
            'pengajuan_dana',
            'pengadaan_sarpras',
            'perbaikan_sarpras',
            'pemberitahuan_ujian_dosen',
            'pengumuman_ujian_mahasiswa',
            'perbaikan_data_pddikti',
            'sk_dekan_pembimbing_mbkm',
            'pembukaan_periode',
            'pengajuan_ttd_ijazah',
            'pengajuan_blanko_ijazah_transkrip',
            'rekomendasi_pinjaman_bjb',
        ])) {
            abort(403, 'Surat ini hanya dapat diterbitkan oleh pihak Fakultas / Admin.');
        }

        $signers = collect(AcademicLetterService::getAvailableSigners())->keyBy('id');
        $defaultSignerId = $letterType->code === 'keterangan_kuliah_pns' ? 'dekan' : 'kaprodi';
        $selectedSigner = $signers->get($validated['signer_id'] ?? $defaultSignerId, $signers->get($defaultSignerId));

        $signerName = $selectedSigner['name'] ?? 'BAGUS ALI AKBAR, S.Si., M.Kom';
        $signerTitle = $selectedSigner['signature_title'] ?? 'Ka. Program Studi';

        $letter = LetterRequest::create([
            'user_id' => $user->id,
            'letter_type_id' => $validated['letter_type_id'],
            'student_name' => $validated['student_name'],
            'npm' => $validated['npm'],
            'study_program' => $validated['study_program'] ?? ($user->study_program ?? 'Sistem Informasi'),
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

        // Notify student (konfirmasi pengajuan)
        $user->notify(new SystemActivityNotification(
            title: 'Pengajuan Surat Berhasil',
            message: "Permohonan {$letterTypeName} Anda berhasil diajukan dan sedang diproses oleh pihak Fakultas / Admin.",
            url: route('mahasiswa.letters.index'),
            category: 'letter_submitted',
            icon: 'document',
            letter: $letter
        ));

        // Notify admins & pejabat (pemberitahuan permohonan baru)
        $officials = User::whereIn('role', ['admin', 'pejabat'])->get();
        foreach ($officials as $official) {
            $official->notify(new SystemActivityNotification(
                title: 'Permohonan Surat Baru',
                message: "Mahasiswa {$letter->student_name} ({$letter->npm}) mengajukan {$letterTypeName}.",
                url: route('letters.show', $letter->uuid),
                category: 'letter_submitted',
                icon: 'inbox',
                letter: $letter
            ));
        }

        return redirect()->route('mahasiswa.letters.index')
            ->with('success', 'Permohonan surat berhasil diajukan! Berkas Anda sedang menunggu verifikasi & tanda tangan elektronik pejabat Fasilkom.');
    }

    /**
     * Show form for editing an existing letter request (only before signed).
     */
    public function edit(LetterRequest $letter, Request $request): Response|RedirectResponse
    {
        $user = $request->user();
        if ($letter->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($letter->status === 'signed') {
            return redirect()->route('mahasiswa.letters.index')
                ->with('error', 'Surat yang sudah ditandatangani secara elektronik (TTE) tidak dapat diubah.');
        }

        $letterTypes = LetterType::where('is_active', true)
            ->where('code', 'not like', 'sk_%')
            ->whereNotIn('code', [
                'tugas_mengajar',
                'tugas_pembimbing_mbkm',
                'tugas_pembimbing_ta',
                'undangan_rapat_akademik',
                'pengantar_magang',
                'pengantar_pkl',
                'keterangan_yudisium',
                'kesanggupan_mengajar',
                'pakta_integritas',
                'pengajuan_dana',
                'pengadaan_sarpras',
                'perbaikan_sarpras',
                'pemberitahuan_ujian_dosen',
                'pengumuman_ujian_mahasiswa',
                'perbaikan_data_pddikti',
                'sk_dekan_pembimbing_mbkm',
                'pembukaan_periode',
                'pengajuan_ttd_ijazah',
                'pengajuan_blanko_ijazah_transkrip',
                'rekomendasi_pinjaman_bjb',
                'mou_magang_mbkm',
            ])
            ->orderBy('order')
            ->get();
        $signers = AcademicLetterService::getAvailableSigners();

        $courses = Course::active()->orderBy('name')->get([
            'id', 'code', 'name', 'sks', 'semester', 'study_program',
        ]);

        return Inertia::render('Mahasiswa/Edit', [
            'letter' => $letter->load('letterType'),
            'letterTypes' => $letterTypes,
            'signers' => $signers,
            'courses' => $courses,
            'user' => $user,
        ]);
    }

    /**
     * Update existing letter request and regenerate standard PDF document.
     */
    public function update(LetterRequest $letter, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($letter->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($letter->status === 'signed') {
            return redirect()->route('mahasiswa.letters.index')
                ->with('error', 'Surat yang sudah ditandatangani secara elektronik (TTE) tidak dapat diubah.');
        }

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
            'extra_data' => ['nullable', 'array'],
        ]);

        $letterType = LetterType::findOrFail($validated['letter_type_id']);
        if (str_starts_with($letterType->code, 'sk_') || in_array($letterType->code, [
            'tugas_mengajar',
            'tugas_pembimbing_mbkm',
            'tugas_pembimbing_ta',
            'undangan_rapat_akademik',
            'pengantar_magang',
            'pengantar_pkl',
            'keterangan_yudisium',
            'kesanggupan_mengajar',
            'pakta_integritas',
            'pengajuan_dana',
            'pengadaan_sarpras',
            'perbaikan_sarpras',
            'pemberitahuan_ujian_dosen',
            'pengumuman_ujian_mahasiswa',
            'perbaikan_data_pddikti',
            'sk_dekan_pembimbing_mbkm',
            'pembukaan_periode',
            'pengajuan_ttd_ijazah',
            'pengajuan_blanko_ijazah_transkrip',
            'rekomendasi_pinjaman_bjb',
        ])) {
            abort(403, 'Surat ini hanya dapat diterbitkan oleh pihak Fakultas / Admin.');
        }

        $signers = collect(AcademicLetterService::getAvailableSigners())->keyBy('id');
        $defaultSignerId = $letterType->code === 'keterangan_kuliah_pns' ? 'dekan' : 'kaprodi';
        $selectedSigner = $signers->get($validated['signer_id'] ?? $defaultSignerId, $signers->get($defaultSignerId));

        $signerName = $selectedSigner['name'] ?? $letter->signer_name ?? 'BAGUS ALI AKBAR, S.Si., M.Kom';
        $signerTitle = $selectedSigner['signature_title'] ?? $letter->signer_title ?? 'Ka. Program Studi';

        $letter->update([
            'letter_type_id' => $validated['letter_type_id'],
            'student_name' => $validated['student_name'],
            'npm' => $validated['npm'],
            'study_program' => $validated['study_program'] ?? ($user->study_program ?? 'Sistem Informasi'),
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'semester' => $validated['semester'],
            'academic_year' => $validated['academic_year'],
            'purpose' => $validated['purpose'] ?? null,
            'extra_data' => $validated['extra_data'] ?? [],
            'signer_name' => $signerName,
            'signer_title' => $signerTitle,
            // Jika sebelumnya ditolak dan mahasiswa memperbaiki data, kembalikan status ke submitted
            'status' => 'submitted',
            'rejection_reason' => null,
        ]);

        // Regenerate updated standard PDF document
        $this->letterService->generatePdf($letter);

        return redirect()->route('mahasiswa.letters.index')
            ->with('success', 'Data pengajuan surat berhasil diperbarui!');
    }

    /**
     * Cancel/delete letter request by student (only allowed before signed).
     */
    public function destroy(LetterRequest $letter, Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($letter->user_id !== $user->id) {
            abort(403, 'Akses ditolak.');
        }

        if ($letter->status === 'signed') {
            return redirect()->route('mahasiswa.letters.index')
                ->with('error', 'Surat yang sudah ditandatangani secara elektronik (TTE) tidak dapat dibatalkan.');
        }

        // Delete associated generated PDF document & file if exists
        if ($letter->document) {
            if ($letter->document->file_path && Storage::disk('local')->exists($letter->document->file_path)) {
                Storage::disk('local')->delete($letter->document->file_path);
            }
            if ($letter->document->signed_file_path && Storage::disk('local')->exists($letter->document->signed_file_path)) {
                Storage::disk('local')->delete($letter->document->signed_file_path);
            }
            $letter->document->delete();
        }

        $letterTypeName = $letter->letterType?->name ?? 'Permohonan surat';
        $letter->delete();

        return redirect()->route('mahasiswa.letters.index')
            ->with('success', "{$letterTypeName} berhasil dibatalkan dan dihapus.");
    }

    /**
     * Display listing of current student's letter requests (Riwayat Pengajuan).
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        $query = LetterRequest::where('user_id', $user->id)->with(['letterType', 'document']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('letter_number', 'like', "%{$search}%")
                    ->orWhere('purpose', 'like', "%{$search}%")
                    ->orWhereHas('letterType', fn ($sub) => $sub->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', $request->input('status'));
        }

        $letters = $query->latest()->paginate(10)->withQueryString();

        $stats = [
            'total' => LetterRequest::where('user_id', $user->id)->count(),
            'submitted' => LetterRequest::where('user_id', $user->id)->where('status', 'submitted')->count(),
            'signed' => LetterRequest::where('user_id', $user->id)->where('status', 'signed')->count(),
            'rejected' => LetterRequest::where('user_id', $user->id)->where('status', 'rejected')->count(),
        ];

        return Inertia::render('Mahasiswa/Riwayat', [
            'letters' => $letters,
            'filters' => $request->only(['search', 'status']),
            'stats' => $stats,
        ]);
    }

    /**
     * Download the official generated or signed letter.
     */
    public function download(LetterRequest $letter, Request $request): BinaryFileResponse|RedirectResponse
    {
        $user = $request->user();
        if ($letter->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'Akses ditolak.');
        }

        // Cek apakah admin mengizinkan unduhan PDF bagi mahasiswa
        if (! $user->isAdmin() && ! $letter->can_download_pdf) {
            $note = $letter->pickup_note ?: 'Surat ini dicetak oleh Fakultas. Silakan ambil fisik surat di bagian Tata Usaha / Akademik Fakultas Ilmu Komputer.';

            return back()->with('error', $note);
        }

        if (! $letter->document) {
            return back()->with('error', 'Dokumen PDF belum tersedia.');
        }

        $targetPath = ($letter->document->isSigned() && $letter->document->signed_file_path)
            ? $letter->document->signed_file_path
            : $letter->document->file_path;

        $fullPath = Storage::disk('local')->path($targetPath);

        if (! file_exists($fullPath)) {
            return back()->with('error', 'Berkas PDF tidak ditemukan pada server.');
        }

        $filename = Str::slug($letter->letterType->name.'-'.$letter->student_name).'.pdf';

        return response()->download($fullPath, $filename, [
            'Content-Type' => 'application/pdf',
        ]);
    }
}
