<?php

use App\Models\Document;
use App\Models\SignatureField;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

function createDummyPdfContent(): string
{
    $pdf = new FPDF;
    $pdf->AddPage();
    $pdf->SetFont('Helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Dokumen Perjanjian Kerja Sama Resmi', 0, 1, 'C');

    return $pdf->Output('S');
}

test('user can upload a PDF document and redirected to studio', function () {
    $user = User::factory()->create();
    $pdfContent = createDummyPdfContent();
    $file = UploadedFile::fake()->createWithContent('perjanjian.pdf', $pdfContent);

    $response = $this->actingAs($user)->post(route('documents.store'), [
        'title' => 'Perjanjian Kerja Sama 2026',
        'pdf_file' => $file,
        'signing_method' => 'visual_and_qr',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('documents', [
        'user_id' => $user->id,
        'title' => 'Perjanjian Kerja Sama 2026',
        'status' => 'draft',
    ]);

    $doc = Document::where('user_id', $user->id)->first();
    expect($doc)->not->toBeNull()
        ->and($doc->total_pages)->toBe(1)
        ->and($doc->sha256_original)->not->toBeEmpty()
        ->and(Storage::disk('local')->exists($doc->file_path))->toBeTrue();
});

test('user can add a signature field on a document page', function () {
    $user = User::factory()->create();
    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'total_pages' => 2,
    ]);

    $response = $this->actingAs($user)->post(route('documents.fields.store', $doc->uuid), [
        'signer_name' => 'Bagus Ali Akbar',
        'signer_email' => 'bagus@example.com',
        'page_number' => 1,
        'pos_x' => 60.5,
        'pos_y' => 75.0,
        'field_type' => 'signature',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('signature_fields', [
        'document_id' => $doc->id,
        'signer_name' => 'Bagus Ali Akbar',
        'page_number' => 1,
        'status' => 'pending',
    ]);
});

test('adding a qr_code field marks it as signed and ready to seal immediately', function () {
    $user = User::factory()->create();
    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'total_pages' => 2,
    ]);

    $response = $this->actingAs($user)->post(route('documents.fields.store', $doc->uuid), [
        'signer_name' => 'Bagus Ali Akbar',
        'signer_email' => 'bagus@example.com',
        'page_number' => 1,
        'pos_x' => 70.0,
        'pos_y' => 80.0,
        'field_type' => 'qr_code',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('signature_fields', [
        'document_id' => $doc->id,
        'field_type' => 'qr_code',
        'status' => 'signed',
        'width' => 16,
        'height' => 16,
    ]);
});

test('user can sign a field with canvas drawing or typed text', function () {
    $user = User::factory()->create();
    $doc = Document::factory()->create(['user_id' => $user->id]);
    $field = SignatureField::factory()->create([
        'document_id' => $doc->id,
        'signer_name' => 'Bagus Ali Akbar',
    ]);

    $response = $this->actingAs($user)->post(
        route('documents.fields.sign', [$doc->uuid, $field->id]),
        [
            'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            'signature_type' => 'canvas_drawing',
        ]
    );

    $response->assertRedirect();
    $this->assertDatabaseHas('signature_fields', [
        'id' => $field->id,
        'status' => 'signed',
        'signature_type' => 'canvas_drawing',
    ]);
});

test('user can finalize and seal document with local signer and QR code', function () {
    $user = User::factory()->create();

    // Store a real dummy PDF in storage
    $pdfContent = createDummyPdfContent();
    $docPath = 'documents/'.$user->id.'/test.pdf';
    Storage::disk('local')->put($docPath, $pdfContent);

    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'file_path' => $docPath,
        'status' => 'draft',
    ]);

    SignatureField::factory()->create([
        'document_id' => $doc->id,
        'page_number' => 1,
        'pos_x' => 50,
        'pos_y' => 70,
        'status' => 'signed',
        'signature_data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
    ]);

    $response = $this->actingAs($user)->post(route('documents.process-sign', $doc->uuid), [
        'driver' => 'local',
    ]);

    $response->assertRedirect();

    $doc->refresh();
    expect($doc->status)->toBe('signed')
        ->and($doc->signed_file_path)->not->toBeNull()
        ->and($doc->sha256_signed)->not->toBeNull()
        ->and(Storage::disk('local')->exists($doc->signed_file_path))->toBeTrue();
});

test('public user can verify a signed document using verification token', function () {
    $user = User::factory()->create();
    $pdfContent = createDummyPdfContent();
    $signedPath = 'documents/signed/test_signed.pdf';
    Storage::disk('local')->put($signedPath, $pdfContent);
    $sha256 = hash('sha256', $pdfContent);

    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'status' => 'signed',
        'signed_file_path' => $signedPath,
        'sha256_signed' => $sha256,
        'verification_token' => 'VERIFY-TOKEN-12345',
    ]);

    $response = $this->get(route('documents.verify', 'VERIFY-TOKEN-12345'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('Verification/Show')
        ->where('verification.found', true)
        ->where('verification.is_intact', true)
    );
});

test('guest, mahasiswa, and admin can access verification index portal', function () {
    // 1. Guest
    $this->get(route('verify.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Verification/Index'));

    // 2. Mahasiswa
    $mahasiswa = User::factory()->mahasiswa()->create([
        'phone' => '08123456789',
        'birth_place' => 'Subang',
        'birth_date' => '2002-05-10',
        'address' => 'Jl. Kartini No. 1, Subang',
    ]);
    $this->actingAs($mahasiswa)->get(route('verify.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Verification/Index'));

    // 3. Admin
    $admin = User::factory()->create();
    $this->actingAs($admin)->get(route('verify.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Verification/Index'));
});

test('authenticated user can stream their PDF document', function () {
    $user = User::factory()->create();
    $pdfContent = createDummyPdfContent();
    $filePath = 'documents/1/test_stream.pdf';
    Storage::disk('local')->put($filePath, $pdfContent);

    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'file_path' => $filePath,
    ]);

    $response = $this->actingAs($user)->get(route('documents.stream', $doc->uuid));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
});

test('authenticated user can preview dynamically stamped PDF before signing', function () {
    $user = User::factory()->create();
    $pdfContent = createDummyPdfContent();
    $filePath = 'documents/1/test_preview.pdf';
    Storage::disk('local')->put($filePath, $pdfContent);

    $doc = Document::factory()->create([
        'user_id' => $user->id,
        'file_path' => $filePath,
        'status' => 'draft',
    ]);

    SignatureField::create([
        'document_id' => $doc->id,
        'signer_name' => 'Bagus Ali Akbar',
        'signer_email' => 'bagus@example.com',
        'field_type' => 'qr_code',
        'page_number' => 1,
        'pos_x' => 50,
        'pos_y' => 60,
        'width' => 16,
        'height' => 16,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->get(route('documents.preview-pdf', $doc->uuid));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($doc->fresh()->status)->toBe('draft');
});

test('admin and pejabat can access studio and adjust fields of a document owned by another user', function () {
    $student = User::factory()->mahasiswa()->create([
        'phone' => '08123456789',
        'birth_place' => 'Subang',
        'birth_date' => '2002-05-10',
        'address' => 'Jl. Kartini No. 1, Subang',
    ]);
    $admin = User::factory()->create(['role' => 'admin']);
    $pejabat = User::factory()->create(['role' => 'pejabat']);
    $otherStudent = User::factory()->mahasiswa()->create([
        'phone' => '08123456788',
        'birth_place' => 'Subang',
        'birth_date' => '2002-05-11',
        'address' => 'Jl. Kartini No. 2, Subang',
    ]);

    $pdfContent = createDummyPdfContent();
    $filePath = 'documents/1/test_admin_studio.pdf';
    Storage::disk('local')->put($filePath, $pdfContent);

    $doc = Document::factory()->create([
        'user_id' => $student->id,
        'file_path' => $filePath,
        'status' => 'draft',
    ]);

    $field = SignatureField::create([
        'document_id' => $doc->id,
        'signer_name' => 'Bagus Ali Akbar',
        'signer_email' => 'bagus@example.com',
        'field_type' => 'signature',
        'page_number' => 1,
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'pending',
    ]);

    // Admin can open studio
    $this->actingAs($admin)->get(route('documents.studio', $doc->uuid))->assertOk();

    // Pejabat can open studio
    $this->actingAs($pejabat)->get(route('documents.studio', $doc->uuid))->assertOk();

    // Admin can update field position
    $this->actingAs($admin)
        ->patch(route('documents.fields.update', [$doc->uuid, $field->id]), [
            'pos_x' => 55.5,
            'pos_y' => 65.5,
        ])
        ->assertRedirect();

    expect($field->fresh())
        ->pos_x->toBe(55.5)
        ->pos_y->toBe(65.5);

    // Admin can stream PDF
    $this->actingAs($admin)->get(route('documents.stream', $doc->uuid))->assertOk();

    // Other student gets 403 Forbidden
    $this->actingAs($otherStudent)->get(route('documents.studio', $doc->uuid))->assertForbidden();
    $this->actingAs($otherStudent)->get(route('documents.stream', $doc->uuid))->assertForbidden();
});
