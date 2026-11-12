<?php

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use App\Services\MouService;
use Database\Seeders\LetterTypeSeeder;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
});

test('mou service renders 6 page pdf without errors', function () {
    $mouService = app(MouService::class);
    $simulatedLetter = new LetterRequest([
        'extra_data' => [
            'partner_name' => 'PT Solusi Teknologi Nusantara',
            'partner_mou_number' => '012/HRD-MOU/XI/2026',
            'faculty_mou_number' => '001/MOU/FIK-US/XI/2026',
            'signing_date' => '2026-11-07',
            'signing_city' => 'Subang',
            'signing_day_words' => 'Sabtu',
            'signing_date_words' => 'tujuh',
            'signing_month_words' => 'November',
            'signing_year_words' => 'dua ribu dua puluh enam',
            'signing_date_formatted' => '07-11-2026',
            'partner_official_name' => 'Budi Santoso, S.T., M.M.',
            'partner_official_position' => 'Direktur Utama',
            'partner_address' => 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan',
            'partner_business_field' => 'Pengembangan Perangkat Lunak',
            'faculty_official_name' => 'Dr. Tepi Peirisal, S.Sos, M.Si',
            'faculty_official_position' => 'Dekan Fakultas Ilmu Komputer Universitas Subang',
            'faculty_nik' => '0410420142',
        ],
    ]);

    $pdf = $mouService->renderMouPdf($simulatedLetter, true);

    expect($pdf)->not->toBeEmpty();
    expect(strlen($pdf))->toBeGreaterThan(5000);
});

test('mahasiswa cannot access mou studio', function () {
    $student = User::factory()->mahasiswa()->create([
        'phone' => '08123456789',
        'birth_place' => 'Subang',
        'birth_date' => '2002-05-10',
        'address' => 'Jl. Kartini No. 1, Subang',
    ]);

    $response = $this->actingAs($student)->get(route('letters.mou-studio'));
    $response->assertForbidden();
});

test('admin can access mou studio', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('letters.mou-studio'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Letters/MouStudio')
            ->has('mouTypes')
            ->has('signers')
        );
});

test('admin can request live preview pdf from mou studio', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'mou_magang_mbkm')->first() ?? LetterType::first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'academic_year' => '2025/2026',
        'extra_data' => [
            'partner_name' => 'PT Solusi Teknologi Nusantara',
            'partner_mou_number' => '012/HRD-MOU/XI/2026',
            'faculty_mou_number' => '001/MOU/FIK-US/XI/2026',
            'signing_date' => '2026-11-07',
        ],
    ];

    $response = $this->actingAs($admin)->postJson(route('letters.mou-studio.preview'), $payload);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'pdfBase64',
        ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('pdfBase64'))->not->toBeEmpty();
});

test('admin can store mou document and create letter request', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'mou_magang_mbkm')->first() ?? LetterType::first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'academic_year' => '2025/2026',
        'signer_id' => 'dekan',
        'extra_data' => [
            'partner_name' => 'PT Solusi Teknologi Nusantara',
            'partner_business_field' => 'Pengembangan Perangkat Lunak',
            'partner_address' => 'Jl. Jenderal Sudirman Kav. 52-53, Jakarta Selatan',
            'partner_official_name' => 'Budi Santoso, S.T., M.M.',
            'partner_official_position' => 'Direktur Utama',
            'partner_mou_number' => '012/HRD-MOU/XI/2026',
            'faculty_mou_number' => '001/MOU/FIK-US/XI/2026',
            'signing_date' => '2026-11-07',
            'signing_city' => 'Subang',
            'faculty_official_name' => 'Dr. Tepi Peirisal, S.Sos, M.Si',
            'faculty_official_position' => 'Dekan Fakultas Ilmu Komputer Universitas Subang',
            'faculty_nik' => '0410420142',
            'program_duration_months' => 5,
            'agreement_duration_years' => 2,
            'notice_days' => 30,
            'obligation_months' => 3,
            'confidentiality_duration_years' => 5,
            'partner_phone' => '(021) 5219988',
            'partner_email' => 'kerjasama@solusiteknologi.co.id',
            'faculty_phone' => '(0260) 411415',
            'faculty_email' => 'fasilkom@unsub.ac.id',
        ],
    ];

    $response = $this->actingAs($admin)->post(route('letters.mou-studio.store'), $payload);

    $response->assertRedirect();
    $this->assertDatabaseHas('letter_requests', [
        'letter_type_id' => $letterType->id,
        'npm' => 'MOU-MITRA',
        'letter_number' => '001/MOU/FIK-US/XI/2026',
    ]);
});
