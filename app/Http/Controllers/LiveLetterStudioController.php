<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\DigitalCertificate;
use App\Models\Lecturer;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use App\Services\AcademicLetterService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveLetterStudioController extends Controller
{
    public function __construct(
        protected AcademicLetterService $letterService
    ) {}

    /**
     * Display the Live Letter Studio interface for Admin & Officials.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $letterTypes = LetterType::where('is_active', true)
            ->where('code', 'not like', 'sk_%')
            ->where('code', 'not like', '%mou%')
            ->where('name', 'not like', 'SK %')
            ->where('name', 'not like', 'Surat Keputusan%')
            ->where('name', 'not like', '%MOU%')
            ->where('name', 'not like', '%MoU%')
            ->where('name', 'not like', '%Nota Kesepahaman%')
            ->orderBy('order')
            ->get();

        $signers = AcademicLetterService::getAvailableSigners();

        $activeCertificate = DigitalCertificate::where('is_active', true)->first();

        $lecturers = Lecturer::active()->orderBy('name')->get([
            'id', 'name', 'nidn', 'nip', 'functional_position', 'academic_rank', 'study_program',
        ]);

        // Optional list of registered students for quick search/auto-fill
        $students = User::where(function ($query) {
            $query->where('role', 'mahasiswa')
                ->orWhere('email', 'like', '%@mhs.unsub.ac.id%');
        })
            ->select('id', 'name', 'email', 'npm', 'study_program', 'phone', 'birth_place', 'birth_date', 'address', 'semester', 'academic_year')
            ->orderBy('name')
            ->limit(100)
            ->get();

        $courses = Course::active()->orderBy('name')->get([
            'id', 'code', 'name', 'sks', 'semester', 'study_program',
        ]);

        return Inertia::render('Letters/LiveStudio', [
            'letterTypes' => $letterTypes,
            'signers' => $signers,
            'hasActiveCertificate' => (bool) $activeCertificate,
            'activeCertificateInfo' => $activeCertificate ? [
                'name' => $activeCertificate->name,
                'organization' => $activeCertificate->organization,
                'organizational_unit' => $activeCertificate->organizational_unit,
                'valid_to' => $activeCertificate->valid_to?->translatedFormat('d F Y'),
            ] : null,
            'students' => $students,
            'lecturers' => $lecturers,
            'courses' => $courses,
            'user' => $user,
        ]);
    }

    /**
     * Generate debounced live PDF preview without persisting data.
     */
    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $letterTypeId = $request->input('letter_type_id');
        $letterType = LetterType::find($letterTypeId) ?? LetterType::where('is_active', true)->first();

        if (! $letterType) {
            return response()->json([
                'success' => false,
                'message' => 'Jenis surat tidak valid atau belum terdaftar.',
            ], 422);
        }

        // Allow on-the-fly custom template overrides if provided
        $previewType = clone $letterType;
        if ($request->filled('opening_template')) {
            $previewType->opening_template = $request->input('opening_template');
        }
        if ($request->filled('content_template')) {
            $previewType->content_template = $request->input('content_template');
        }
        if ($request->filled('closing_template')) {
            $previewType->closing_template = $request->input('closing_template');
        }

        // Map signer details
        $signerId = $request->input('signer_id', 'kaprodi');
        $signers = collect(AcademicLetterService::getAvailableSigners(false));
        $selectedSigner = $signers->firstWhere('id', $signerId)
            ?? $signers->firstWhere('code', $signerId)
            ?? [
                'name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
                'signature_title' => 'Ka. Program Studi',
            ];

        $extraData = (array) $request->input('extra_data', []);
        if (! empty($request->input('opening_template'))) {
            $extraData['opening_template'] = $request->input('opening_template');
        }
        if (! empty($request->input('content_template'))) {
            $extraData['content_template'] = $request->input('content_template');
        }
        if (! empty($request->input('closing_template'))) {
            $extraData['closing_template'] = $request->input('closing_template');
        }
        if (in_array($previewType->code, ['kesanggupan_mengajar', 'pakta_integritas'])) {
            $signerName = ! empty($extraData['lecturer_name']) ? $extraData['lecturer_name'] : ($request->input('student_name') ?: 'Dosen Pengampu');
            $signerTitle = 'Dosen Pengampu';
        } else {
            $signerName = $selectedSigner['name'];
            $signerTitle = $selectedSigner['signature_title'];
        }

        // Construct in-memory LetterRequest
        $birthDate = $request->input('birth_date');
        if ($birthDate) {
            try {
                $birthDate = Carbon::parse($birthDate)->toDateString();
            } catch (\Throwable) {
                $birthDate = '2003-05-14';
            }
        }

        $letter = new LetterRequest([
            'user_id' => $user->id,
            'letter_type_id' => $previewType->id,
            'student_name' => $request->input('student_name') ?: 'Nama Pemohon / Mahasiswa',
            'npm' => $request->input('npm') ?: 'D1A210001',
            'study_program' => $request->input('study_program') ?: 'Sistem Informasi',
            'birth_place' => $request->input('birth_place') ?: 'Subang',
            'birth_date' => $birthDate ?: '2003-05-14',
            'address' => $request->input('address') ?: 'Jl. R.A. Kartini No. 12 Subang',
            'semester' => (int) ($request->input('semester') ?: 6),
            'academic_year' => $request->input('academic_year') ?: '2026/2027',
            'purpose' => $request->input('purpose') ?: 'Keperluan Administrasi Akademik',
            'extra_data' => $extraData,
            'signer_name' => $signerName,
            'signer_title' => $signerTitle,
            'status' => 'draft',
        ]);

        $letter->setRelation('letterType', $previewType);

        try {
            $pdfContent = $this->letterService->renderPreviewPdf($letter, true);

            return response()->json([
                'success' => true,
                'pdfBase64' => base64_encode($pdfContent),
                'letterType' => [
                    'id' => $previewType->id,
                    'name' => $previewType->name,
                    'letter_code' => $previewType->letter_code,
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal merender simulasi surat: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store and optionally immediately sign (TTE) the letter.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $validated = $request->validate([
            'letter_type_id' => ['required', 'exists:letter_types,id'],
            'student_name' => ['required', 'string', 'max:255'],
            'npm' => ['required', 'string', 'max:50'],
            'study_program' => ['required', 'string', 'max:255'],
            'birth_place' => ['nullable', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'address' => ['nullable', 'string'],
            'semester' => ['nullable', 'integer', 'min:1', 'max:14'],
            'academic_year' => ['nullable', 'string', 'max:20'],
            'purpose' => ['nullable', 'string'],
            'signer_id' => ['required', 'string', 'max:50'],
            'extra_data' => ['nullable', 'array'],
            'direct_sign' => ['nullable', 'boolean'],
            'user_id' => ['nullable', 'exists:users,id'],
            'opening_template' => ['nullable', 'string'],
            'content_template' => ['nullable', 'string'],
            'closing_template' => ['nullable', 'string'],
        ]);

        $letterType = LetterType::findOrFail($validated['letter_type_id']);
        $isNonStudentLetter = in_array($letterType->code, [
            'tugas_mengajar',
            'tugas_pembimbing_mbkm',
            'tugas_pembimbing_ta',
            'undangan_rapat_akademik',
            'kesanggupan_mengajar',
            'pakta_integritas',
            'pengajuan_dana',
            'pengadaan_sarpras',
            'perbaikan_sarpras',
            'pemberitahuan_ujian_dosen',
            'pengumuman_ujian_mahasiswa',
            'perbaikan_data_pddikti',
            'pembukaan_periode',
            'pengajuan_ttd_ijazah',
            'pengajuan_blanko_ijazah_transkrip',
            'rekomendasi_pinjaman_bjb',
        ]);

        if (! $isNonStudentLetter && empty($validated['semester'])) {
            return back()->withErrors(['semester' => 'Semester wajib diisi untuk surat pemohon mahasiswa.']);
        }

        $semesterVal = $validated['semester'] ?? 1;

        // Map signer details
        $signers = collect(AcademicLetterService::getAvailableSigners(false));
        $selectedSigner = $signers->firstWhere('id', $validated['signer_id'])
            ?? $signers->firstWhere('code', $validated['signer_id'])
            ?? [
                'name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
                'signature_title' => 'Ka. Program Studi',
            ];

        $applicantUserId = $validated['user_id'] ?? $user->id;

        $extraData = $validated['extra_data'] ?? [];
        if (! empty($validated['opening_template'])) {
            $extraData['opening_template'] = $validated['opening_template'];
        }
        if (! empty($validated['content_template'])) {
            $extraData['content_template'] = $validated['content_template'];
        }
        if (! empty($validated['closing_template'])) {
            $extraData['closing_template'] = $validated['closing_template'];
        }

        if (in_array($letterType->code, ['kesanggupan_mengajar', 'pakta_integritas'])) {
            $signerName = ! empty($extraData['lecturer_name']) ? $extraData['lecturer_name'] : $validated['student_name'];
            $signerTitle = 'Dosen Pengampu';
        } else {
            $signerName = $selectedSigner['name'];
            $signerTitle = $selectedSigner['signature_title'];
        }

        $letter = LetterRequest::create([
            'user_id' => $applicantUserId,
            'letter_type_id' => $validated['letter_type_id'],
            'student_name' => $validated['student_name'],
            'npm' => $validated['npm'],
            'study_program' => $validated['study_program'],
            'birth_place' => $validated['birth_place'] ?? null,
            'birth_date' => $validated['birth_date'] ?? null,
            'address' => $validated['address'] ?? null,
            'semester' => $semesterVal,
            'academic_year' => $validated['academic_year'] ?? '2025/2026',
            'purpose' => $validated['purpose'] ?? 'Kelengkapan Berkas Administrasi Akademik',
            'extra_data' => $extraData,
            'signer_name' => $signerName,
            'signer_title' => $signerTitle,
            'status' => 'submitted',
        ]);

        // Generate standard draft PDF with official letter number
        $this->letterService->generatePdf($letter);
        $letter->refresh();

        // One-Click Direct Sign & Seal with Digital Certificate (TTE PKI X.509)
        if ($request->boolean('direct_sign')) {
            $this->letterService->approveAndSeal($letter);
            $letter->refresh();

            return redirect()->route('letters.show', $letter->uuid)
                ->with('success', "Surat resmi {$letter->letterType->name} ({$letter->letter_number}) berhasil diterbitkan dan langsung ditandatangani secara elektronik (TTE) berlogo resmi Universitas Subang.");
        }

        return redirect()->route('letters.show', $letter->uuid)
            ->with('success', "Surat resmi {$letter->letterType->name} berhasil dibuat. Dokumen siap ditandatangani oleh {$letter->signer_title}.");
    }
}
