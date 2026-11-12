<?php

use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\SignatureField;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
    Storage::fake('local');
});

// ==========================================
// 1. DOUBLE-SIGNING PREVENTION ON DOCUMENTS
// ==========================================

test('document signing endpoint rejects re-processing an already signed document', function () {
    $user = User::factory()->create();

    $doc = Document::create([
        'user_id' => $user->id,
        'title' => 'Dokumen Berita Acara',
        'original_filename' => 'ba.pdf',
        'file_path' => 'documents/ba.pdf',
        'signed_file_path' => 'documents/signed/ba_signed.pdf',
        'file_size' => 1024,
        'total_pages' => 1,
        'sha256_original' => hash('sha256', 'original'),
        'sha256_signed' => 'INITIAL-SIGNED-HASH-12345',
        'status' => 'signed',
        'verification_token' => 'TOKEN-123',
    ]);

    // Attempting to sign again
    $response = $this->actingAs($user)->post(route('documents.process-sign', $doc->uuid), [
        'driver' => 'local',
    ]);

    $response->assertSessionHas('error', 'Dokumen ini sudah ditandatangani sebelumnya.');

    $doc->refresh();
    expect($doc->sha256_signed)->toBe('INITIAL-SIGNED-HASH-12345');
    expect($doc->status)->toBe('signed');
});

test('signature field cannot be signed if the parent document is already finalized', function () {
    $user = User::factory()->create();

    $doc = Document::create([
        'user_id' => $user->id,
        'title' => 'Dokumen Final',
        'original_filename' => 'final.pdf',
        'file_path' => 'documents/final.pdf',
        'file_size' => 1024,
        'total_pages' => 1,
        'sha256_original' => hash('sha256', 'original'),
        'status' => 'signed',
    ]);

    $field = SignatureField::create([
        'document_id' => $doc->id,
        'signer_name' => $user->name,
        'signer_email' => $user->email,
        'field_type' => 'signature',
        'page_number' => 1,
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'pending',
    ]);

    $response = $this->actingAs($user)->post(route('documents.fields.sign', [$doc->uuid, $field->id]), [
        'signature_data' => 'data:image/png;base64,mock',
        'signature_type' => 'canvas_drawing',
    ]);

    $response->assertSessionHas('error', 'Dokumen telah difinalisasi.');
    expect($field->fresh()->status)->toBe('pending');
});

test('signature field signing rejects mismatched document id (field hijacking)', function () {
    $user = User::factory()->create();

    $docA = Document::create([
        'user_id' => $user->id,
        'title' => 'Dokumen A',
        'original_filename' => 'doc_a.pdf',
        'file_path' => 'documents/a.pdf',
        'file_size' => 1024,
        'total_pages' => 1,
        'sha256_original' => hash('sha256', 'a'),
        'status' => 'draft',
    ]);

    $docB = Document::create([
        'user_id' => $user->id,
        'title' => 'Dokumen B',
        'original_filename' => 'doc_b.pdf',
        'file_path' => 'documents/b.pdf',
        'file_size' => 1024,
        'total_pages' => 1,
        'sha256_original' => hash('sha256', 'b'),
        'status' => 'draft',
    ]);

    // Field belongs to Document B
    $fieldB = SignatureField::create([
        'document_id' => $docB->id,
        'signer_name' => $user->name,
        'signer_email' => $user->email,
        'field_type' => 'signature',
        'page_number' => 1,
        'pos_x' => 10,
        'pos_y' => 20,
        'status' => 'pending',
    ]);

    // Attacking docA endpoint with fieldB ID
    $response = $this->actingAs($user)->post(route('documents.fields.sign', [$docA->uuid, $fieldB->id]), [
        'signature_data' => 'data:image/png;base64,mock',
        'signature_type' => 'canvas_drawing',
    ]);

    $response->assertForbidden();
    expect($fieldB->fresh()->status)->toBe('pending');
});

// ==========================================
// 2. DOUBLE-SIGNING & STATE LOCK ON LETTERS
// ==========================================

test('academic letter request cannot be signed more than once', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->mahasiswa()->create();
    $type = LetterType::first();

    $originalSignedAt = Carbon::create(2026, 11, 1, 10, 0, 0);

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $type->id,
        'student_name' => $student->name,
        'npm' => 'D1A260099',
        'study_program' => 'Sistem Informasi',
        'semester' => 3,
        'academic_year' => '2026/2027',
        'status' => 'signed',
        'letter_number' => '045/FIK-US/KET/XI/2026',
        'signed_at' => $originalSignedAt,
    ]);

    // Attempting to re-sign already signed letter
    $response = $this->actingAs($admin)->post(route('letters.sign', $letter->uuid));

    $response->assertSessionHas('error', 'Surat ini telah ditandatangani.');

    $letter->refresh();
    expect($letter->status)->toBe('signed');
    expect($letter->signed_at->timestamp)->toBe($originalSignedAt->timestamp);
});

test('designated signer cannot be changed once letter request is signed', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->mahasiswa()->create();
    $type = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $type->id,
        'student_name' => $student->name,
        'npm' => 'D1A260099',
        'study_program' => 'Sistem Informasi',
        'semester' => 3,
        'academic_year' => '2026/2027',
        'status' => 'signed',
        'signer_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'signer_title' => 'Ka. Program Studi',
        'signed_at' => now(),
    ]);

    $response = $this->actingAs($admin)->patch(route('letters.update-signer', $letter->uuid), [
        'signer_id' => 'dekan',
    ]);

    $response->assertSessionHas('error', 'Surat yang sudah ditandatangani tidak dapat diubah penandatangannya.');

    $letter->refresh();
    expect($letter->signer_name)->toBe('BAGUS ALI AKBAR, S.Si., M.Kom');
});

test('mahasiswa cannot edit or delete their own letter request once signed', function () {
    $student = User::factory()->mahasiswa()->create(['npm' => 'D1A260088']);
    $type = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $type->id,
        'student_name' => $student->name,
        'npm' => 'D1A260088',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
        'status' => 'signed',
        'letter_number' => '100/FIK-US/KET/XI/2026',
        'signed_at' => now(),
    ]);

    // Attempt to update
    $updateResponse = $this->actingAs($student)->put(route('mahasiswa.letters.update', $letter->uuid), [
        'letter_type_id' => $type->id,
        'student_name' => 'Attempted Modification',
        'npm' => 'D1A260088',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
    ]);

    $updateResponse->assertSessionHas('error', 'Surat yang sudah ditandatangani secara elektronik (TTE) tidak dapat diubah.');

    // Attempt to delete
    $deleteResponse = $this->actingAs($student)->delete(route('mahasiswa.letters.destroy', $letter->uuid));
    $deleteResponse->assertSessionHas('error', 'Surat yang sudah ditandatangani secara elektronik (TTE) tidak dapat dibatalkan.');

    $letter->refresh();
    expect($letter->student_name)->toBe($student->name);
    expect(LetterRequest::where('uuid', $letter->uuid)->exists())->toBeTrue();
});

