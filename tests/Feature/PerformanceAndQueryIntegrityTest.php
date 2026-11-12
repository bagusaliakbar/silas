<?php

use App\Models\Archive;
use App\Models\ArchiveCategory;
use App\Models\Document;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
    Storage::fake('local');
});

// ==========================================
// 1. N+1 PREVENTION ON ADMIN LETTERS INDEX
// ==========================================

test('admin letters listing does not suffer from N+1 query problem with multiple records', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->mahasiswa()->create();
    $letterType = LetterType::first();

    // Create 15 letters with documents
    for ($i = 1; $i <= 15; $i++) {
        $letter = LetterRequest::create([
            'user_id' => $student->id,
            'letter_type_id' => $letterType->id,
            'student_name' => "Mahasiswa Test {$i}",
            'npm' => 'D1A2600'.str_pad($i, 2, '0', STR_PAD_LEFT),
            'study_program' => 'Sistem Informasi',
            'semester' => 3,
            'academic_year' => '2026/2027',
            'status' => 'submitted',
        ]);

        Document::create([
            'user_id' => $student->id,
            'title' => "Document {$i}",
            'original_filename' => "doc_{$i}.pdf",
            'file_path' => "documents/doc_{$i}.pdf",
            'file_size' => 1024,
            'total_pages' => 1,
            'sha256_original' => hash('sha256', "content_{$i}"),
            'status' => 'draft',
            'metadata' => ['letter_request_id' => $letter->id],
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($admin)->get(route('letters.index'));
    $response->assertOk();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // With 15 items, pagination count + eager loads (letterType, document, user, archive) + stats counts
    // Should be strictly bounded (O(1) relative to number of letter records) and well under 15 queries
    expect(count($queries))->toBeLessThanOrEqual(15);

    // Verify relations are eagerly loaded on paginated items
    $pageLetters = $response->viewData('page')['props']['letters']['data'] ?? [];
    expect($pageLetters)->not->toBeEmpty();
});

// ==========================================
// 2. N+1 PREVENTION ON MAHASISWA RIWAYAT
// ==========================================

test('mahasiswa riwayat listing scales efficiently without per-record queries', function () {
    $student = User::factory()->mahasiswa()->create();
    $letterType = LetterType::first();

    // Seed 10 letters for this student
    for ($i = 1; $i <= 10; $i++) {
        LetterRequest::create([
            'user_id' => $student->id,
            'letter_type_id' => $letterType->id,
            'student_name' => $student->name,
            'npm' => 'D1A2600'.str_pad($i, 2, '0', STR_PAD_LEFT),
            'study_program' => 'Sistem Informasi',
            'semester' => 2,
            'academic_year' => '2026/2027',
            'status' => 'submitted',
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($student)->get(route('mahasiswa.letters.index'));
    $response->assertOk();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Total queries for student riwayat must be bounded (auth/session + paginate count + items + eager loads)
    expect(count($queries))->toBeLessThanOrEqual(10);
});

// ==========================================
// 3. N+1 PREVENTION ON PUSAT ARSIP INDEX
// ==========================================

test('archives index eager loads taxonomy and letter relationships with bounded queries', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $category = ArchiveCategory::create([
        'name' => 'Kategori Uji Kinerja',
        'code' => 'TEST_PERF',
        'letter_direction' => 'both',
        'color' => 'blue',
        'is_active' => true,
    ]);

    // Seed 12 archives
    for ($i = 1; $i <= 12; $i++) {
        Archive::create([
            'title' => "Arsip Uji Kinerja {$i}",
            'reference_number' => "REF/PERF/{$i}",
            'letter_date' => '2026-11-01',
            'letter_type' => 'keluar',
            'archive_category_id' => $category->id,
            'created_by' => $admin->id,
        ]);
    }

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($admin)->get(route('archives.index'));
    $response->assertOk();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Bounded queries for archives (items eager loading category, creator, incomingLetter, letterRequest)
    expect(count($queries))->toBeLessThanOrEqual(15);
});

// ==========================================
// 4. DASHBOARD QUERY COUNT EFFICIENCY
// ==========================================

test('admin dashboard query count remains bounded across statistics aggregations', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertOk();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Aggregates for letter stats, document stats, certificates, and recent widgets
    expect(count($queries))->toBeLessThanOrEqual(18);
});

// ==========================================
// 5. LIGHTWEIGHT VERIFICATION LOOKUP
// ==========================================

test('public verification token lookup performs minimal database queries', function () {
    $user = User::factory()->create();

    $doc = Document::create([
        'user_id' => $user->id,
        'title' => 'Dokumen Terverifikasi',
        'original_filename' => 'valid.pdf',
        'file_path' => 'documents/valid.pdf',
        'signed_file_path' => 'documents/signed/valid_signed.pdf',
        'file_size' => 1024,
        'total_pages' => 1,
        'sha256_original' => hash('sha256', 'original'),
        'sha256_signed' => hash('sha256', 'signed'),
        'status' => 'signed',
        'verification_token' => 'PERF-TOKEN-TEST-001',
    ]);

    Storage::disk('local')->put($doc->signed_file_path, '%PDF-1.4 test');

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->get(route('documents.verify', 'PERF-TOKEN-TEST-001'));
    $response->assertOk();

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    // Verification lookup should be extremely lightweight (<= 6 queries including session & audit log insert)
    expect(count($queries))->toBeLessThanOrEqual(6);
});

