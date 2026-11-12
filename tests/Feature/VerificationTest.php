<?php

use App\Models\AuditLog;
use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\SignatureField;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(LetterTypeSeeder::class);
});

function generateTestPdf(string $title = 'Dokumen Resmi Test'): string
{
    $pdf = new FPDF;
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 14);
    $pdf->Cell(0, 10, $title, 0, 1, 'C');
    $pdf->Ln(10);
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->MultiCell(0, 7, 'Isi dokumen resmi ber-TTE Fakultas Ilmu Komputer Universitas Subang untuk pengujian integritas.');

    return $pdf->Output('S');
}

test('public verification index page can be accessed by guest', function () {
    $response = $this->get(route('verification.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('Verification/Index'));
});

test('guest can verify authentic signed document via verification token and audit log is recorded', function () {
    $user = User::factory()->create();
    $pdfContent = generateTestPdf('Surat Keterangan Mahasiswa Aktif');
    $filePath = 'documents/signed/test_signed_letter.pdf';
    Storage::disk('local')->put($filePath, $pdfContent);
    $hashSigned = hash('sha256', $pdfContent);

    $token = Str::random(32);

    $cert = DigitalCertificate::factory()->create([
        'user_id' => $user->id,
        'name' => 'Dr. TEPI PEIRISAL, M.Si',
        'organization' => 'Fakultas Ilmu Komputer Universitas Subang',
    ]);

    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'title' => 'Surat Keterangan Kuliah - Bagus Ali Akbar',
        'file_path' => $filePath,
        'signed_file_path' => $filePath,
        'sha256_original' => $hashSigned,
        'sha256_signed' => $hashSigned,
        'status' => 'signed',
        'verification_token' => $token,
        'signed_at' => now(),
        'digital_certificate_id' => $cert->id,
    ]);

    $letterType = LetterType::first();
    LetterRequest::create([
        'user_id' => $user->id,
        'document_id' => $doc->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Bagus Ali Akbar',
        'npm' => 'D1A220001',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
        'letter_number' => '421/001/FASILKOM/2026',
        'status' => 'signed',
    ]);

    SignatureField::create([
        'document_id' => $doc->id,
        'signer_name' => 'Dr. TEPI PEIRISAL, M.Si',
        'signer_email' => 'tepipeirisal@unsub.ac.id',
        'page_number' => 1,
        'pos_x' => 120,
        'pos_y' => 200,
        'field_type' => 'signature',
        'status' => 'signed',
        'signed_at' => now(),
    ]);

    $response = $this->get(route('documents.verify', $token));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Verification/Show')
            ->where('verification.found', true)
            ->where('verification.is_intact', true)
            ->where('verification.status', 'valid')
            ->where('verification.tamper_detected', false)
            ->where('token', $token)
            ->has('qrDataUri')
        );

    // Verify AuditLog is properly recorded with IP
    $this->assertDatabaseHas('audit_logs', [
        'document_id' => $doc->id,
        'action' => 'verified',
    ]);
});

test('public verification of non-existent token returns not found status safely', function () {
    $randomToken = 'invalid-token-uuid-' . Str::random(16);

    $response = $this->get(route('documents.verify', $randomToken));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Verification/Show')
            ->where('verification.found', false)
            ->where('verification.status', 'not_found')
            ->where('token', $randomToken)
        );
});

test('guest can verify authentic signed pdf file upload', function () {
    $user = User::factory()->create();
    $pdfContent = generateTestPdf('Surat Rekomendasi Magang MBKM');
    $filePath = 'documents/signed/magang_signed.pdf';
    Storage::disk('local')->put($filePath, $pdfContent);
    $hashSigned = hash('sha256', $pdfContent);

    $token = Str::random(32);

    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'title' => 'Surat Rekomendasi MBKM',
        'file_path' => $filePath,
        'signed_file_path' => $filePath,
        'sha256_signed' => $hashSigned,
        'status' => 'signed',
        'verification_token' => $token,
        'signed_at' => now(),
    ]);

    $uploadedFile = UploadedFile::fake()->createWithContent('surat_mbkm_asli.pdf', $pdfContent);

    $response = $this->post(route('verification.file'), [
        'pdf_file' => $uploadedFile,
    ]);

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Verification/Index')
            ->where('verificationResult.found', true)
            ->where('verificationResult.is_intact', true)
            ->where('verificationResult.status', 'valid')
        );
});

test('guest uploading tampered or modified pdf file reports tamper detected and hash mismatch', function () {
    // Generate original PDF and register in DB
    $originalPdf = generateTestPdf('Surat Asli dari Dekan');
    $hashOriginal = hash('sha256', $originalPdf);

    Document::factory()->create([
        'title' => 'Dokumen Dekan Terdaftar',
        'sha256_signed' => $hashOriginal,
        'status' => 'signed',
    ]);

    // Create a modified/tampered PDF file with altered content
    $tamperedPdf = generateTestPdf('Surat Asli dari Dekan - DIUBAH SECARA ILEGAL');
    $uploadedTampered = UploadedFile::fake()->createWithContent('surat_diubah.pdf', $tamperedPdf);

    $response = $this->post(route('verification.file'), [
        'pdf_file' => $uploadedTampered,
    ]);

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Verification/Index')
            ->where('verificationResult.found', false)
            ->where('verificationResult.status', 'not_found')
            ->where('verificationResult.tamper_detected', true)
        );
});

test('public verification rejects non-pdf file uploads with validation error', function () {
    $nonPdfFile = UploadedFile::fake()->create('malicious.exe', 500, 'application/x-msdownload');

    $response = $this->post(route('verification.file'), [
        'pdf_file' => $nonPdfFile,
    ]);

    $response->assertSessionHasErrors('pdf_file');
});

