<?php

use App\Models\DigitalCertificate;
use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\SignatureField;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
    Storage::fake('local');
});

// ==========================================
// 1. MAHASISWA IDOR & WORKFLOW ESCALATION
// ==========================================

test('mahasiswa cannot view edit page or update another student letter request (IDOR)', function () {
    $studentA = User::factory()->mahasiswa()->create(['npm' => 'D1A200001']);
    $studentB = User::factory()->mahasiswa()->create(['npm' => 'D1A200002']);
    $type = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $studentA->id,
        'letter_type_id' => $type->id,
        'student_name' => 'Mahasiswa A',
        'npm' => 'D1A200001',
        'study_program' => 'Sistem Informasi',
        'semester' => 3,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    // Student B attempts to open edit form of Student A's letter
    $this->actingAs($studentB)
        ->get(route('mahasiswa.letters.edit', $letter->uuid))
        ->assertForbidden();

    // Student B attempts to update Student A's letter
    $this->actingAs($studentB)
        ->put(route('mahasiswa.letters.update', $letter->uuid), [
            'letter_type_id' => $type->id,
            'student_name' => 'Hacked Name',
            'npm' => 'D1A200002',
            'study_program' => 'Sistem Informasi',
            'semester' => 3,
            'academic_year' => '2026/2027',
        ])
        ->assertForbidden();

    expect($letter->fresh()->student_name)->toBe('Mahasiswa A');
});

test('mahasiswa cannot delete or download another student letter request (IDOR)', function () {
    $studentA = User::factory()->mahasiswa()->create(['npm' => 'D1A200001']);
    $studentB = User::factory()->mahasiswa()->create(['npm' => 'D1A200002']);
    $type = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $studentA->id,
        'letter_type_id' => $type->id,
        'student_name' => 'Mahasiswa A',
        'npm' => 'D1A200001',
        'study_program' => 'Sistem Informasi',
        'semester' => 3,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    // Delete attempt by Student B
    $this->actingAs($studentB)
        ->delete(route('mahasiswa.letters.destroy', $letter->uuid))
        ->assertForbidden();

    // Download attempt by Student B
    $this->actingAs($studentB)
        ->get(route('mahasiswa.letters.download', $letter->uuid))
        ->assertForbidden();

    expect(LetterRequest::where('uuid', $letter->uuid)->exists())->toBeTrue();
});

test('mahasiswa cannot forge user_id or status during letter submission', function () {
    $studentA = User::factory()->mahasiswa()->create(['npm' => 'D1A200001']);
    $studentB = User::factory()->mahasiswa()->create(['npm' => 'D1A200002']);
    $type = LetterType::first();

    // Student A attempts to submit on behalf of Student B with pre-approved signed status
    $response = $this->actingAs($studentA)->post(route('mahasiswa.letters.store'), [
        'user_id' => $studentB->id,
        'letter_type_id' => $type->id,
        'student_name' => 'Forged Submission',
        'npm' => 'D1A200002',
        'study_program' => 'Sistem Informasi',
        'semester' => 3,
        'academic_year' => '2026/2027',
        'status' => 'signed',
        'letter_number' => '999/ILLEGAL/2026',
    ]);

    $response->assertRedirect(route('mahasiswa.letters.index'));

    $created = LetterRequest::where('student_name', 'Forged Submission')->first();
    expect($created)->not->toBeNull();
    // System must bind to authenticated user, not forged payload
    expect($created->user_id)->toBe($studentA->id);
    expect($created->status)->toBe('submitted');
});

// ==========================================
// 2. PRIVILEGE ESCALATION ON LETTER ACTIONS
// ==========================================

test('mahasiswa cannot perform administrative letter actions (sign, reject, updateSigner, toggleDownload)', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $admin = User::factory()->create(['role' => 'admin']);
    $type = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $mahasiswa->id,
        'letter_type_id' => $type->id,
        'student_name' => $mahasiswa->name,
        'npm' => 'D1A260001',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    // 1. Mahasiswa cannot sign letter
    $this->actingAs($mahasiswa)
        ->post(route('letters.sign', $letter->uuid))
        ->assertForbidden();

    // 2. Mahasiswa cannot reject letter
    $this->actingAs($mahasiswa)
        ->post(route('letters.reject', $letter->uuid), ['rejection_reason' => 'Unauthorized'])
        ->assertForbidden();

    // 3. Mahasiswa cannot update signer
    $this->actingAs($mahasiswa)
        ->patch(route('letters.update-signer', $letter->uuid), ['signer_id' => 'dekan'])
        ->assertForbidden();

    // 4. Mahasiswa cannot toggle download permission
    $this->actingAs($mahasiswa)
        ->patch(route('letters.toggle-download', $letter->uuid), ['can_download_pdf' => true])
        ->assertForbidden();

    // 5. Mahasiswa cannot upload manual signed file
    $file = UploadedFile::fake()->create('manual.pdf', 500, 'application/pdf');
    $this->actingAs($mahasiswa)
        ->post(route('letters.upload-manual-signed', $letter->uuid), ['signed_file' => $file])
        ->assertForbidden();

    expect($letter->fresh()->status)->toBe('submitted');
});

// ==========================================
// 3. DIGITAL CERTIFICATE IDOR
// ==========================================

test('user cannot access, set default, download, or delete another user digital certificate (IDOR)', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $certPath = 'certificates/'.$userA->id.'/cert.crt';
    Storage::disk('local')->put($certPath, '-----BEGIN CERTIFICATE-----MOCK-----END CERTIFICATE-----');

    $certA = DigitalCertificate::create([
        'user_id' => $userA->id,
        'name' => 'Sertifikat Pejabat A',
        'serial_number' => 'SERIAL-A-001',
        'certificate_path' => $certPath,
        'valid_from' => now(),
        'valid_to' => now()->addYear(),
        'is_active' => true,
        'is_default' => true,
    ]);

    // User B attempts to set User A's certificate as default
    $this->actingAs($userB)
        ->post(route('certificates.default', $certA->id))
        ->assertForbidden();

    // User B attempts to download User A's certificate
    $this->actingAs($userB)
        ->get(route('certificates.download', $certA->id))
        ->assertForbidden();

    // User B attempts to delete User A's certificate
    $this->actingAs($userB)
        ->delete(route('certificates.destroy', $certA->id))
        ->assertForbidden();

    expect(DigitalCertificate::where('id', $certA->id)->exists())->toBeTrue();
    Storage::disk('local')->assertExists($certPath);
});

test('downloading certificate with missing physical file returns 404', function () {
    $user = User::factory()->create();

    $cert = DigitalCertificate::create([
        'user_id' => $user->id,
        'name' => 'Ghost Certificate',
        'serial_number' => 'GHOST-001',
        'certificate_path' => 'certificates/non_existent.crt',
        'valid_from' => now(),
        'valid_to' => now()->addYear(),
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->get(route('certificates.download', $cert->id));
    $response->assertNotFound();
});

// ==========================================
// 4. DOCUMENT STUDIO IDOR & ACCESS BOUNDARY
// ==========================================

test('unrelated user cannot access studio, stream, sign field, or process signing of another user document', function () {
    $owner = User::factory()->create();
    $stranger = User::factory()->create();

    $filePath = 'documents/'.$owner->id.'/contract.pdf';
    Storage::disk('local')->put($filePath, '%PDF-1.4 mock content');

    $doc = Document::create([
        'user_id' => $owner->id,
        'title' => 'Kontrak Rahasia',
        'original_filename' => 'contract.pdf',
        'file_path' => $filePath,
        'file_size' => 1024,
        'total_pages' => 1,
        'sha256_original' => hash('sha256', '%PDF-1.4 mock content'),
        'status' => 'draft',
        'verification_token' => 'SECRET-TOKEN-123',
    ]);

    $field = SignatureField::create([
        'document_id' => $doc->id,
        'signer_name' => $owner->name,
        'signer_email' => $owner->email,
        'field_type' => 'signature',
        'page_number' => 1,
        'pos_x' => 10,
        'pos_y' => 10,
        'status' => 'pending',
    ]);

    // Stranger cannot open studio
    $this->actingAs($stranger)
        ->get(route('documents.studio', $doc->uuid))
        ->assertForbidden();

    // Stranger cannot stream pdf
    $this->actingAs($stranger)
        ->get(route('documents.stream', $doc->uuid))
        ->assertForbidden();

    // Stranger cannot sign field
    $this->actingAs($stranger)
        ->post(route('documents.fields.sign', [$doc->uuid, $field->id]), [
            'signature_data' => 'data:image/png;base64,mock',
            'signature_type' => 'canvas_drawing',
        ])
        ->assertForbidden();

    // Stranger cannot process sign
    $this->actingAs($stranger)
        ->post(route('documents.process-sign', $doc->uuid), [
            'driver' => 'local',
        ])
        ->assertForbidden();
});

// ==========================================
// 5. FILE UPLOAD BOUNDARIES & EXTENSION GUARDS
// ==========================================

test('document upload rejects non-pdf and oversized files', function () {
    $user = User::factory()->create();

    // 1. Executable file disguised with name
    $fakeScript = UploadedFile::fake()->create('malicious.php', 100, 'text/x-php');
    $responsePhp = $this->actingAs($user)->post(route('documents.store'), [
        'title' => 'Exploit Test',
        'pdf_file' => $fakeScript,
    ]);
    $responsePhp->assertSessionHasErrors(['pdf_file']);

    // 2. Oversized PDF (greater than 20MB / 20480 KB)
    $oversizedPdf = UploadedFile::fake()->create('huge.pdf', 25000, 'application/pdf');
    $responseOversize = $this->actingAs($user)->post(route('documents.store'), [
        'title' => 'Oversized PDF Test',
        'pdf_file' => $oversizedPdf,
    ]);
    $responseOversize->assertSessionHasErrors(['pdf_file']);
});

test('archive upload rejects unsupported file extensions', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $shFile = UploadedFile::fake()->create('script.sh', 50, 'application/x-sh');
    $response = $this->actingAs($admin)->post(route('archives.store'), [
        'title' => 'Shell Script',
        'letter_date' => '2026-11-01',
        'letter_type' => 'lainnya',
        'file' => $shFile,
    ]);

    $response->assertSessionHasErrors(['file']);
    expect(Document::count())->toBe(0);
});

