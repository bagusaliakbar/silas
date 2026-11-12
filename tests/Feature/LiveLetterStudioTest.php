<?php

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
});

test('mahasiswa cannot access live letter studio', function () {
    $student = User::factory()->mahasiswa()->create([
        'phone' => '081234567890',
        'birth_place' => 'Subang',
        'birth_date' => '2004-01-01',
        'address' => 'Subang',
    ]);

    $response = $this->actingAs($student)->get(route('letters.live-studio'));
    $response->assertForbidden();
});

test('admin can access live letter studio', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('letters.live-studio'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Letters/LiveStudio')
            ->has('letterTypes')
            ->has('signers')
        );
});

test('admin can request live preview pdf from studio', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Budi Santoso',
        'npm' => 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 6,
        'academic_year' => '2026/2027',
        'signer_id' => 'kaprodi',
    ];

    $response = $this->actingAs($admin)->postJson(route('letters.live-studio.preview'), $payload);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'pdfBase64',
            'letterType' => ['id', 'name', 'letter_code'],
        ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('pdfBase64'))->not->toBeEmpty();
});

test('admin can store new letter from studio as submitted', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'pengantar_magang')->first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Bagus Ali Akbar',
        'npm' => 'D1A260029',
        'study_program' => 'Sistem Informasi',
        'semester' => 6,
        'academic_year' => '2026/2027',
        'purpose' => 'Magang MBKM',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'target_agency' => 'PT. Dahana (Persero)',
            'internship_duration' => '5 bulan',
        ],
        'direct_sign' => false,
    ];

    $response = $this->actingAs($admin)->post(route('letters.live-studio.store'), $payload);

    $letter = LetterRequest::where('student_name', 'Bagus Ali Akbar')->first();
    expect($letter)->not->toBeNull();
    expect($letter->status)->toBe('submitted');

    $response->assertRedirect(route('letters.show', $letter->uuid));
});

test('admin can store and directly sign letter with tte from studio', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'keterangan_kuliah_non_pns')->first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Luna Amella Wardhani',
        'npm' => 'D1A260029',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'purpose' => 'Administrasi Beasiswa',
        'signer_id' => 'kaprodi',
        'direct_sign' => true,
    ];

    $response = $this->actingAs($admin)->post(route('letters.live-studio.store'), $payload);

    $letter = LetterRequest::where('student_name', 'Luna Amella Wardhani')->latest()->first();
    expect($letter)->not->toBeNull();
    expect($letter->status)->toBe('signed');
    expect($letter->signed_at)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));
});

test('admin can preview and store observasi matkul letter from studio with course and group members', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'observasi_matkul')->first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Alvito Bagaswara',
        'npm' => 'D1A260009',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
        'purpose' => 'Observasi Mata Kuliah',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'target_agency' => 'Kantor Samsat Kabupaten Subang',
            'target_recipient' => 'Kepala Kantor Samsat Kabupaten Subang',
            'course_name' => 'Rekayasa Perangkat Lunak',
            'lecturer_name' => 'Bambang Tjahjo Utomo, MT',
            'target_address' => 'Jl. Mayjen Sutoyo No. 3 Subang',
            'group_members' => [
                ['name' => 'M. Fadhil Aziz', 'npm' => 'D1A260007', 'address' => 'SUBANG'],
            ],
        ],
        'direct_sign' => false,
    ];

    $previewResponse = $this->actingAs($admin)->postJson(route('letters.live-studio.preview'), $payload);
    $previewResponse->assertOk();
    expect($previewResponse->json('success'))->toBeTrue();

    $storeResponse = $this->actingAs($admin)->post(route('letters.live-studio.store'), $payload);
    $letter = LetterRequest::where('student_name', 'Alvito Bagaswara')->latest()->first();
    expect($letter)->not->toBeNull();
    expect($letter->extra_data['course_name'])->toBe('Rekayasa Perangkat Lunak');
    expect($letter->extra_data['lecturer_name'])->toBe('Bambang Tjahjo Utomo, MT');
    expect($letter->extra_data)->not->toHaveKey('internship_duration');

    $storeResponse->assertRedirect(route('letters.show', $letter->uuid));
});

test('admin can preview letter with empty input fields gracefully', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'student_name' => '',
        'npm' => '',
        'study_program' => '',
        'birth_place' => '',
        'birth_date' => '',
        'address' => '',
        'semester' => '',
        'academic_year' => '',
        'purpose' => '',
        'signer_id' => 'kaprodi',
        'extra_data' => [],
    ];

    $response = $this->actingAs($admin)->postJson(route('letters.live-studio.preview'), $payload);

    $response->assertOk();
    expect($response->json('success'))->toBeTrue();
    expect($response->json('pdfBase64'))->not->toBeEmpty();
});
