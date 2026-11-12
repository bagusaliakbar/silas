<?php

use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
});

test('mahasiswa cannot access sk studio', function () {
    $student = User::factory()->mahasiswa()->create([
        'phone' => '08123456789',
        'birth_place' => 'Subang',
        'birth_date' => '2002-05-10',
        'address' => 'Jl. Kartini No. 1, Subang',
    ]);

    $response = $this->actingAs($student)->get(route('letters.sk-studio'));
    $response->assertForbidden();
});

test('admin can access sk studio', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('letters.sk-studio'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Letters/SkStudio')
            ->has('skTypes')
            ->has('signers')
        );
});

test('admin can request live preview pdf from sk studio', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'sk_dekan_pembimbing_mbkm')->first()
        ?? LetterType::where('letter_code', 'D/FIK-US')->first()
        ?? LetterType::first();

    $payload = [
        'letter_type_id' => $letterType->id,
        'academic_year' => '2025/2026',
        'extra_data' => [
            'about_title' => 'PENYELENGGARAAN DAN PENETAPAN DOSEN PEMBIMBING BKP MBKM',
            'semester_type' => 'Ganjil',
            'academic_year' => '2025/2026',
            'decision_date_text' => '01 September 2025',
            'menimbang' => [
                'Bahwa dalam rangka kebijakan merdeka belajar...',
            ],
            'mengingat' => [
                'Undang-undang Republik Indonesia Nomor 12 Tahun 2012...',
            ],
            'dictums' => [
                ['label' => 'KESATU', 'content' => 'Menetapkan Dosen Pembimbing BKP MBKM...'],
            ],
            'tembusan_list' => [
                'Yth. Rektor Universitas Subang',
            ],
        ],
    ];

    $response = $this->actingAs($admin)->postJson(route('letters.sk-studio.preview'), $payload);

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'pdfBase64',
        ]);

    expect($response->json('success'))->toBeTrue();
    expect($response->json('pdfBase64'))->not->toBeEmpty();
});
