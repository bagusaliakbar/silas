<?php

namespace App\Http\Controllers;

use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\SignatureField;
use App\Services\AcademicLetterService;
use App\Services\MouService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MouStudioController extends Controller
{
    public function __construct(
        protected MouService $mouService,
        protected AcademicLetterService $letterService
    ) {}

    /**
     * Display the Studio MoU interface for Admin & Officials.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $mouTypes = LetterType::where('is_active', true)
            ->where(function ($query) {
                $query->where('code', 'like', '%mou%')
                    ->orWhere('name', 'like', '%MOU%')
                    ->orWhere('name', 'like', '%Nota Kesepahaman%')
                    ->orWhere('name', 'like', '%MoU%');
            })
            ->orderBy('order')
            ->get();

        if ($mouTypes->isEmpty()) {
            $mouTypes = LetterType::where('code', 'mou_magang_mbkm')->get();
        }

        $signers = AcademicLetterService::getAvailableSigners();
        $activeCertificate = DigitalCertificate::where('is_active', true)->first();

        return Inertia::render('Letters/MouStudio', [
            'mouTypes' => $mouTypes,
            'signers' => $signers,
            'hasActiveCertificate' => (bool) $activeCertificate,
            'activeCertificateInfo' => $activeCertificate ? [
                'name' => $activeCertificate->name,
                'organization' => $activeCertificate->organization,
                'organizational_unit' => $activeCertificate->organizational_unit,
                'valid_to' => $activeCertificate->valid_to?->translatedFormat('d F Y'),
            ] : null,
            'user' => $user,
        ]);
    }

    /**
     * Generate debounced live PDF preview for MoU.
     */
    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->isMahasiswa()) {
            abort(403, 'Akses terbatas untuk Administrator dan Pejabat Fasilkom.');
        }

        $letterTypeId = $request->input('letter_type_id');
        $letterType = LetterType::find($letterTypeId) ?? LetterType::where('code', 'mou_magang_mbkm')->first();

        $extraData = $request->input('extra_data', []);

        // Auto compute date in words if date is provided
        if (! empty($extraData['signing_date'])) {
            $dateDetails = $this->computeDateDetails($extraData['signing_date']);
            foreach ($dateDetails as $key => $val) {
                if (empty($extraData[$key])) {
                    $extraData[$key] = $val;
                }
            }
        }

        $partnerName = $extraData['partner_name'] ?? '(Institusi / Perusahaan)';
        $partnerOfficial = $extraData['partner_official_name'] ?? 'Pihak Mitra';

        $simulatedLetter = new LetterRequest([
            'user_id' => $user->id,
            'letter_type_id' => $letterType?->id,
            'student_name' => $partnerOfficial.' ('.$partnerName.')',
            'npm' => 'MOU-MITRA',
            'study_program' => 'Fakultas Ilmu Komputer',
            'semester' => 1,
            'academic_year' => $request->input('academic_year', '2025/2026'),
            'letter_number' => $extraData['faculty_mou_number'] ?? '.../MOU/FIK-US/XI/2026',
            'signer_name' => $request->input('signer_name', 'Dr. Tepi Peirisal, S.Sos, M.Si'),
            'signer_title' => $request->input('signer_title', 'Dekan'),
            'extra_data' => $extraData,
            'status' => 'draft',
        ]);
        $simulatedLetter->setRelation('letterType', $letterType);

        try {
            $pdfContent = $this->mouService->renderMouPdf($simulatedLetter, true);

            return response()->json([
                'success' => true,
                'pdfBase64' => base64_encode($pdfContent),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal merender simulasi MoU: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store and optionally direct-sign the MoU document.
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
            'signer_id' => ['nullable', 'string', 'max:50'],
            'extra_data' => ['required', 'array'],
            'direct_sign' => ['nullable', 'boolean'],
        ]);

        $letterType = LetterType::findOrFail($validated['letter_type_id']);

        $signers = collect(AcademicLetterService::getAvailableSigners())->keyBy('id');
        $selectedSigner = $signers->get($validated['signer_id'] ?? 'dekan', $signers->get('dekan'));

        $extraData = $validated['extra_data'];

        // Auto compute date in words if date is provided
        if (! empty($extraData['signing_date'])) {
            $dateDetails = $this->computeDateDetails($extraData['signing_date']);
            foreach ($dateDetails as $key => $val) {
                if (empty($extraData[$key])) {
                    $extraData[$key] = $val;
                }
            }
        }

        $partnerName = $extraData['partner_name'] ?? 'Mitra Kerjasama';
        $partnerOfficial = $extraData['partner_official_name'] ?? 'Pimpinan Mitra';

        $mouTemp = new LetterRequest([
            'letter_type_id' => $letterType->id,
            'created_at' => now(),
        ]);
        $mouTemp->setRelation('letterType', $letterType);

        $facultyMouNumber = ! empty($extraData['faculty_mou_number'])
            ? $extraData['faculty_mou_number']
            : $this->letterService->generateLetterNumber($mouTemp);

        $extraData['faculty_mou_number'] = $facultyMouNumber;

        $mou = LetterRequest::create([
            'uuid' => (string) Str::uuid(),
            'user_id' => $user->id,
            'letter_type_id' => $letterType->id,
            'student_name' => $partnerOfficial.' ('.$partnerName.')',
            'npm' => 'MOU-MITRA',
            'study_program' => 'Fakultas Ilmu Komputer',
            'semester' => 1,
            'academic_year' => $validated['academic_year'],
            'purpose' => 'Nota Kesepahaman (MoU) Kerja Sama Magang MBKM',
            'signer_name' => $selectedSigner['name'] ?? 'Dr. Tepi Peirisal, S.Sos, M.Si',
            'signer_title' => $selectedSigner['signature_title'] ?? 'Dekan',
            'letter_number' => $facultyMouNumber,
            'extra_data' => $extraData,
            'status' => 'submitted',
        ]);

        // Generate initial PDF document
        $pdfContent = $this->mouService->renderMouPdf($mou, false);
        $fileName = 'MOU-'.Str::slug($partnerName).'-'.time().'.pdf';
        $directory = 'letters/'.$user->id;
        Storage::disk('local')->makeDirectory($directory);
        $storagePath = $directory.'/'.$fileName;
        Storage::disk('local')->put($storagePath, $pdfContent);
        $sha256 = hash('sha256', $pdfContent);

        $doc = Document::create([
            'user_id' => $user->id,
            'title' => 'MoU - '.$partnerName.' ('.$facultyMouNumber.')',
            'original_filename' => $fileName,
            'file_path' => $storagePath,
            'file_size' => strlen($pdfContent),
            'total_pages' => 6,
            'sha256_original' => $sha256,
            'status' => 'draft',
            'signing_method' => 'visual_and_qr',
            'psre_provider' => 'local',
            'metadata' => [
                'letter_request_id' => $mou->id,
                'letter_number' => $facultyMouNumber,
                'letter_type' => $letterType->name,
                'partner_name' => $partnerName,
                'npm' => 'MOU-MITRA',
                'signer_title' => $selectedSigner['signature_title'] ?? 'Dekan',
                'signer_name' => $selectedSigner['name'] ?? 'Dr. Tepi Peirisal, S.Sos, M.Si',
            ],
        ]);

        // Create SignatureField on Document matching exact signature position (Dekan is on left side of Page 6)
        $sigPage = (int) ($this->mouService->mouSignaturePage ?: 6);
        $sigY = $this->mouService->mouSignatureY ?: 140;
        $posYPercent = round((($sigY + 12) / 297) * 100, 1);
        $signerEmail = $selectedSigner['email'] ?? 'tepipeirisal@unsub.ac.id';

        SignatureField::create([
            'document_id' => $doc->id,
            'user_id' => $user->id,
            'signer_name' => $selectedSigner['name'] ?? 'Dr. Tepi Peirisal, S.Sos, M.Si',
            'signer_email' => $signerEmail,
            'role' => 'signer',
            'page_number' => $sigPage,
            'pos_x' => 19, // Dekan is on left column (x=25 to x=100)
            'pos_y' => $posYPercent,
            'width' => 12,
            'height' => 6.5,
            'field_type' => 'qr_code',
            'status' => 'pending',
        ]);

        $mou->update(['document_id' => $doc->id]);

        // Direct TTE execution if selected
        if (! empty($validated['direct_sign'])) {
            try {
                $this->letterService->approveAndSeal($mou);

                return redirect()->route('letters.show', $mou->uuid)
                    ->with('success', "Nota Kesepahaman (MoU) ({$facultyMouNumber}) berhasil dibuat dan langsung dibubuhi TTE QR Code resmi Dekan!");
            } catch (\Exception $e) {
                return redirect()->route('letters.show', $mou->uuid)
                    ->with('warning', "MoU berhasil dibuat ({$facultyMouNumber}), namun TTE langsung gagal: {$e->getMessage()}. Silakan lakukan TTE manual.");
            }
        }

        return redirect()->route('letters.show', $mou->uuid)
            ->with('success', "Nota Kesepahaman (MoU) ({$facultyMouNumber}) berhasil dibuat dan siap untuk diproses.");
    }

    /**
     * Compute Indonesian date words helper.
     */
    protected function computeDateDetails(string $dateString): array
    {
        try {
            $date = Carbon::parse($dateString);
            $dayNum = (int) $date->format('j');
            $monthNum = (int) $date->format('n');
            $yearNum = (int) $date->format('Y');

            return [
                'signing_day_words' => $this->mouService->getDayNameWords($date->locale('id')->isoFormat('dddd')),
                'signing_date_words' => $this->mouService->numberToWords($dayNum),
                'signing_month_words' => $this->mouService->getMonthName($monthNum),
                'signing_year_words' => $this->mouService->numberToWords($yearNum),
                'signing_date_formatted' => $date->format('d-m-Y'),
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }
}

