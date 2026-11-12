<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\Lecturer;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\SignatureField;
use App\Models\User;
use App\Services\AcademicLetterService;
use App\Services\SkService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SkStudioController extends Controller
{
    public function __construct(
        protected SkService $skService,
        protected AcademicLetterService $letterService
    ) {}

    /**
     * Display the Studio SK interface for Admin & Officials.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $skTypes = LetterType::where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', 'sk_%')
                    ->orWhere('name', 'like', 'SK %')
                    ->orWhere('name', 'like', 'Surat Keputusan%');
            })
            ->orderBy('order')
            ->get();

        // If no SK specific type found, fall back to default
        if ($skTypes->isEmpty()) {
            $skTypes = LetterType::where('code', 'sk_dekan_pembimbing_mbkm')->get();
        }

        $signers = AcademicLetterService::getAvailableSigners();
        $activeCertificate = DigitalCertificate::where('is_active', true)->first();

        $lecturers = Lecturer::active()->orderBy('name')->get([
            'id', 'name', 'nidn', 'nip', 'functional_position', 'academic_rank', 'study_program',
        ]);

        $courses = Course::active()->orderBy('name')->get([
            'id', 'code', 'name', 'sks', 'semester', 'study_program',
        ]);

        $students = User::where(function ($query) {
            $query->where('role', 'mahasiswa')
                ->orWhere('email', 'like', '%@mhs.unsub.ac.id%');
        })
            ->select('id', 'name', 'email', 'npm', 'study_program')
            ->orderBy('name')
            ->limit(100)
            ->get();

        return Inertia::render('Letters/SkStudio', [
            'skTypes' => $skTypes,
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
     * Generate debounced live PDF preview for SK.
     */
    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $letterTypeId = $request->input('letter_type_id');
        $letterType = LetterType::find($letterTypeId) ?? LetterType::where('code', 'sk_dekan_pembimbing_mbkm')->first();

        // Resolve signer
        $signerId = $request->input('signer_id', 'dekan');
        $signers = collect(AcademicLetterService::getAvailableSigners())->keyBy('id');
        $selectedSigner = $signers->get($signerId, $signers->get('dekan'));

        $signerName = $request->input('signer_name') ?: ($selectedSigner['name'] ?? 'Dr. TEPI PEIRISAL, M. Si.');
        $signerTitle = $request->input('signer_title') ?: ($selectedSigner['signature_title'] ?? 'Dekan');

        $extraData = (array) $request->input('extra_data', []);
        if (! empty($selectedSigner['name'])) {
            $extraData['dekan_name'] = $signerName;
        }
        if (! empty($selectedSigner['nip']) && empty($extraData['dekan_nik'])) {
            $extraData['dekan_nik'] = $selectedSigner['nip'];
        }

        $simulatedLetter = new LetterRequest([
            'user_id' => $user->id,
            'letter_type_id' => $letterType?->id,
            'student_name' => $request->input('student_name', $user->name),
            'npm' => $request->input('npm', 'SK-DEKAN'),
            'study_program' => $request->input('study_program', 'Sistem Informasi'),
            'semester' => 1,
            'academic_year' => $request->input('academic_year', '2025/2026'),
            'letter_number' => $request->input('letter_number') ?: ('.../'.($letterType?->letter_code ?? 'D/FIK-US').'/'.Carbon::now()->format('m/Y')),
            'signer_name' => $signerName,
            'signer_title' => $signerTitle,
            'extra_data' => $extraData,
            'status' => 'draft',
        ]);
        $simulatedLetter->setRelation('letterType', $letterType);

        try {
            $pdfContent = $this->skService->renderSkPdf($simulatedLetter, true);

            return response()->json([
                'success' => true,
                'pdfBase64' => base64_encode($pdfContent),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal merender simulasi SK: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store and optionally direct-sign the SK document.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $validated = $request->validate([
            'letter_type_id' => ['required', 'exists:letter_types,id'],
            'academic_year' => ['required', 'string', 'max:50'],
            'letter_number' => ['nullable', 'string', 'max:100'],
            'signer_id' => ['nullable', 'string', 'max:50'],
            'extra_data' => ['nullable', 'array'],
            'direct_sign' => ['nullable', 'boolean'],
        ]);

        $letterType = LetterType::findOrFail($validated['letter_type_id']);

        $signers = collect(AcademicLetterService::getAvailableSigners())->keyBy('id');
        $selectedSigner = $signers->get($validated['signer_id'] ?? 'dekan', $signers->get('dekan'));

        $sk = new LetterRequest([
            'letter_type_id' => $letterType->id,
        ]);
        $sk->setRelation('letterType', $letterType);

        $letterNumber = ! empty($validated['letter_number'])
            ? $validated['letter_number']
            : $this->letterService->generateLetterNumber($sk);

        $sk = LetterRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'letter_type_id' => $letterType->id,
            'student_name' => $selectedSigner['name'] ?? 'Dr. TEPI PEIRISAL, M. Si.',
            'npm' => 'SK-DEKAN',
            'study_program' => 'Fakultas Ilmu Komputer',
            'semester' => 1,
            'academic_year' => $validated['academic_year'],
            'purpose' => 'Surat Keputusan Dekan',
            'signer_name' => $selectedSigner['name'] ?? 'Dr. TEPI PEIRISAL, M. Si.',
            'signer_title' => $selectedSigner['signature_title'] ?? 'Dekan',
            'letter_number' => $letterNumber,
            'extra_data' => $validated['extra_data'] ?? [],
            'status' => 'submitted',
        ]);

        // Generate initial PDF document
        $pdfContent = $this->skService->renderSkPdf($sk, false);
        $fileName = 'SK-'.Str::slug($letterType->name).'-'.time().'.pdf';
        $directory = 'letters/'.$user->id;
        Storage::disk('local')->makeDirectory($directory);
        $storagePath = $directory.'/'.$fileName;
        Storage::disk('local')->put($storagePath, $pdfContent);
        $fullPath = Storage::disk('local')->path($storagePath);
        $sha256 = hash('sha256', $pdfContent);

        $doc = Document::create([
            'user_id' => $user->id,
            'title' => $letterType->name.' - '.$letterNumber,
            'original_filename' => $fileName,
            'file_path' => $storagePath,
            'file_size' => strlen($pdfContent),
            'total_pages' => (int) ($this->skService->skSignaturePage ?: 1),
            'sha256_original' => $sha256,
            'status' => 'draft',
            'signing_method' => 'visual_and_qr',
            'psre_provider' => 'local',
            'metadata' => [
                'letter_request_id' => $sk->id,
                'letter_number' => $letterNumber,
                'letter_type' => $letterType->name,
                'npm' => 'SK-DEKAN',
                'signer_title' => $selectedSigner['signature_title'] ?? 'Dekan',
                'signer_name' => $selectedSigner['name'] ?? 'Dr. TEPI PEIRISAL, M. Si.',
            ],
        ]);

        // Create SignatureField on Document matching exact signature position
        $sigPage = (int) $this->skService->skSignaturePage;
        $sigY = $this->skService->skSignatureY;
        $posYPercent = round((($sigY + 12) / 297) * 100, 1);
        $signerEmail = $selectedSigner['email'] ?? 'tepipeirisal@unsub.ac.id';

        SignatureField::create([
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'signer_name' => $selectedSigner['name'] ?? 'Dr. TEPI PEIRISAL, M. Si.',
            'signer_email' => $signerEmail,
            'role' => 'signer',
            'page_number' => $sigPage,
            'pos_x' => 58,
            'pos_y' => $posYPercent,
            'width' => 11,
            'height' => 6.5,
            'field_type' => 'qr_code',
            'status' => 'pending',
        ]);

        $sk->update(['document_id' => $doc->id]);

        // Direct TTE execution if selected
        if (! empty($validated['direct_sign'])) {
            try {
                $this->letterService->approveAndSeal($sk);

                return redirect()->route('letters.show', $sk->uuid)
                    ->with('success', "Surat Keputusan ({$letterNumber}) berhasil dibuat dan langsung dibubuhi TTE QR Code resmi!");
            } catch (\Exception $e) {
                return redirect()->route('letters.show', $sk->uuid)
                    ->with('warning', "SK berhasil dibuat ({$letterNumber}), namun TTE langsung gagal: {$e->getMessage()}. Silakan lakukan TTE manual.");
            }
        }

        return redirect()->route('letters.show', $sk->uuid)
            ->with('success', "Surat Keputusan ({$letterNumber}) berhasil dibuat dan siap untuk diproses.");
    }
}
