<?php

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    $this->seed(LetterTypeSeeder::class);
});

test('user can view academic letters index page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('letters.index'));

    $response->assertOk();
});

test('user can view letter creation form with active letter types and available signers', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('letters.create'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Letters/Create')
        ->has('letterTypes', 17)
        ->has('signers', 3)
    );
});

test('user can request a letter signed by Dekan or Wakil Dekan', function () {
    $user = User::factory()->create([
        'name' => 'LUNA AMELLA WARDHANI',
        'email' => 'luna@example.com',
    ]);

    $letterType = LetterType::where('code', 'keterangan_kuliah_non_pns')->first();

    // 1. Request with Dekan
    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'signer_id' => 'dekan',
        'student_name' => 'LUNA AMELLA WARDHANI',
        'npm' => 'D1A260088',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
    ]);

    $letter = LetterRequest::where('npm', 'D1A260088')->first();
    expect($letter->signer_name)->toBe('Dr. TEPI PEIRISAL, M.Si')
        ->and($letter->signer_title)->toBe('Dekan')
        ->and($letter->document->signatureFields()->first()->signer_email)->toBe('tepipeirisal@unsub.ac.id');

    // 2. Update signer to Wakil Dekan
    $patchResponse = $this->actingAs($user)->patch(route('letters.update-signer', $letter->uuid), [
        'signer_id' => 'wadek',
    ]);
    $patchResponse->assertRedirect();

    $letter->refresh();
    expect($letter->signer_name)->toBe('BAMBANG TJAHJO UTOMO, MT')
        ->and($letter->signer_title)->toBe('Wakil Dekan')
        ->and($letter->document->signatureFields()->first()->signer_email)->toBe('bambangtjahjoutomo@unsub.ac.id');
});

test('user can request a Surat Keterangan Masih Kuliah and PDF is automatically generated', function () {
    $user = User::factory()->create([
        'name' => 'BUDI SANTOSO',
        'email' => 'budi@example.com',
    ]);

    $letterType = LetterType::where('code', 'keterangan_kuliah_non_pns')->first();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'BUDI SANTOSO',
        'npm' => 'D1A260099',
        'study_program' => 'Sistem Informasi',
        'birth_place' => 'Wonogiri',
        'birth_date' => '2007-01-08',
        'address' => 'Mutiara Jaya Regency C 4/1 RT 041/RW 004 Desa Kondangjaya, Kecamatan Karawang Timur',
        'semester' => 1,
        'academic_year' => '2026/2027',
        'purpose' => 'Persyaratan Administrasi',
    ]);

    $letter = LetterRequest::where('npm', 'D1A260099')->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->not->toBeNull()
        ->and($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->signatureFields()->count())->toBeGreaterThan(0)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('kaprodi can electronically sign and seal the letter with QR code', function () {
    $user = User::factory()->create([
        'name' => 'Bagus Ali Akbar',
        'email' => 'signer@example.com',
    ]);

    $letterType = LetterType::where('code', 'keterangan_kuliah_non_pns')->first();

    $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'LUNA AMELLA WARDHANI',
        'npm' => 'D1A260029',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
    ]);

    $letter = LetterRequest::where('npm', 'D1A260029')->first();
    expect($letter->status)->toBe('submitted');

    // Sign the letter
    $signResponse = $this->actingAs($user)->post(route('letters.sign', $letter->uuid));
    $signResponse->assertRedirect();

    $letter->refresh();
    expect($letter->status)->toBe('signed')
        ->and($letter->signed_at)->not->toBeNull()
        ->and($letter->document->status)->toBe('signed')
        ->and($letter->document->signed_file_path)->not->toBeNull()
        ->and(Storage::disk('local')->exists($letter->document->signed_file_path))->toBeTrue();

    // Download the signed letter
    $downloadResponse = $this->actingAs($user)->get(route('letters.download', $letter->uuid));
    $downloadResponse->assertOk();
});

test('user can request Surat Pengantar Izin Penelitian Skripsi and PDF is correctly generated with table and memo metadata', function () {
    $user = User::factory()->create([
        'name' => 'AGUS CAKRA DINATA',
        'email' => 'agus@example.com',
    ]);

    $letterType = LetterType::where('code', 'izin_penelitian_skripsi')->first();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'AGUS CAKRA DINATA',
        'npm' => 'D1A2300036',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'address' => 'SUBANG',
        'extra_data' => [
            'target_recipient' => 'Kepala Kantor Desa Panyingkiran Kecamatan Purwadadi Kabupaten Subang',
            'target_agency' => 'Kantor Desa Panyingkiran Kecamatan Purwadadi Kabupaten Subang',
            'target_city' => 'Tempat',
            'thesis_title' => 'Rancang Bangun Sistem Informasi Pelayanan Desa',
        ],
    ]);

    $letter = LetterRequest::where('npm', 'D1A2300036')->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('admin can create Surat Tugas Mengajar with teaching schedules and PDF is generated correctly', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $letterType = LetterType::where('code', 'tugas_mengajar')->first();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'npm' => '0410019202',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
        'signer_id' => 'wadek',
        'extra_data' => [
            'lecturer_status' => 'Dosen Tetap',
            'nidn' => '0410019202',
            'semester_type' => 'Ganjil',
            'teaching_schedules' => [
                ['course_name' => 'Desain WEB 1C', 'code' => 'D1A12004', 'sks' => 2, 'day' => 'Kamis', 'time' => '18.30-20.00', 'room' => 'Lab II'],
                ['course_name' => 'Desain WEB 1B', 'code' => 'D1A12004', 'sks' => 2, 'day' => 'Jum\'at', 'time' => '08.30-10.00', 'room' => 'Lab II'],
                ['course_name' => 'Desain WEB 1A', 'code' => 'D1A12004', 'sks' => 2, 'day' => 'Jum\'at', 'time' => '10.00-11.30', 'room' => 'Lab II'],
                ['course_name' => 'Kerja Praktek', 'code' => 'D1A73001', 'sks' => 3, 'day' => 'Sabtu', 'time' => '09.00-10.30', 'room' => '-'],
                ['course_name' => 'Praktek Analisis dan Perancangan Sistem Lanjut', 'code' => 'D1A74102', 'sks' => 4, 'day' => 'Sabtu', 'time' => '14.30-16.00', 'room' => '-'],
                ['course_name' => 'Praktek Analisis dan Perancangan Sistem Lanjut', 'code' => 'D1A73001', 'sks' => 4, 'day' => 'Sabtu', 'time' => '14.30-16.00', 'room' => '-'],
            ],
        ],
    ]);

    $letter = LetterRequest::where('npm', '0410019202')->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->signer_title)->toBe('Wakil Dekan')
        ->and($letter->document_id)->not->toBeNull()
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('user can request Surat Keterangan Mengikuti Seminar Usulan Penelitian and PDF is correctly generated with participants table', function () {
    $user = User::factory()->create([
        'name' => 'SITI MASITOH',
        'email' => 'siti@example.com',
    ]);

    $letterType = LetterType::where('code', 'izin_seminar_up')->first();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'SITI MASITOH',
        'npm' => 'D1A230421',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'address' => 'SUBANG',
        'extra_data' => [
            'target_recipient' => 'HRD PT. Uwu Jump Indonesia',
            'target_city' => 'Tempat',
            'activity_name' => 'Seminar Usulan Penelitian',
            'event_day' => 'Kamis',
        ],
    ]);

    $letter = LetterRequest::where('npm', 'D1A230421')->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('admin can create Surat Tugas Pembimbing MBKM with mentees list and PDF is generated correctly', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $letterType = LetterType::where('code', 'tugas_pembimbing_mbkm')->first();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'npm' => '0410019202',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
        'signer_id' => 'wadek',
        'extra_data' => [
            'lecturer_status' => 'Dosen Tetap',
            'nidn' => '0410019202',
            'semester_type' => 'Ganjil',
            'mbkm_students' => [
                ['name' => 'LIA KARTIKA', 'npm' => 'D1A230409', 'mitra_project' => 'Dicoding (Studi Independen)', 'role' => 'Pembimbing I'],
                ['name' => 'NIKMAH AZIZAH', 'npm' => 'D1A230406', 'mitra_project' => 'Dicoding (Studi Independen)', 'role' => 'Pembimbing I'],
                ['name' => 'BINTA AULIA PUTRI', 'npm' => 'D1A230404', 'mitra_project' => 'Dicoding (Studi Independen)', 'role' => 'Pembimbing I'],
                ['name' => 'YUSUF ABDULLAH', 'npm' => 'D1A230400', 'mitra_project' => 'Dicoding (Studi Independen)', 'role' => 'Pembimbing I'],
                ['name' => 'ROHIDIN', 'npm' => 'D1A230408', 'mitra_project' => "DBS Foundation\n(Studi Independen)", 'role' => 'Pembimbing I'],
                ['name' => 'MUHAMMAD RIDHO AL Z', 'npm' => 'D1A230109', 'mitra_project' => 'Fasilkom (Proyek Independen)', 'role' => 'Pembimbing I'],
            ],
        ],
    ]);

    $letter = LetterRequest::where('npm', '0410019202')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->signer_title)->toBe('Wakil Dekan')
        ->and($letter->document_id)->not->toBeNull()
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('admin can create Surat Tugas Pembimbing Tugas Akhir with mentees list and PDF is generated correctly', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $letterType = LetterType::where('code', 'tugas_pembimbing_ta')->first();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'npm' => '0410019202',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
        'signer_id' => 'wadek',
        'extra_data' => [
            'lecturer_status' => 'Dosen Tetap',
            'nidn' => '0410019202',
            'semester_type' => 'Ganjil',
            'ta_students' => [
                ['name' => 'Tiara Maharani Ar', 'npm' => 'D1A200403', 'role' => 'Pembimbing I'],
                ['name' => 'Riska Nuril Anwar', 'npm' => 'D1A230062', 'role' => 'Pembimbing I'],
                ['name' => 'Nasywaa Nabiilah Nuur\'aini', 'npm' => 'D1A230033', 'role' => 'Pembimbing I'],
                ['name' => 'Tri Meiliyani Kurnia', 'npm' => 'D1A230426', 'role' => 'Pembimbing I'],
                ['name' => 'Tegar Bumi Ramadhan', 'npm' => 'D1A230099', 'role' => 'Pembimbing I'],
                ['name' => 'Nada Savana Dhiya', 'npm' => 'D1A231013', 'role' => 'Pembimbing I'],
                ['name' => 'Pupu Purnama Fujiyaman', 'npm' => 'D1A252002', 'role' => 'Pembimbing I'],
                ['name' => 'Lia Kartika', 'npm' => 'D1A230409', 'role' => 'Pembimbing I'],
            ],
        ],
    ]);

    $letter = LetterRequest::where('npm', '0410019202')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->signer_title)->toBe('Wakil Dekan')
        ->and($letter->document_id)->not->toBeNull()
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('admin can create Surat Keterangan Mengikuti Sidang Tugas Akhir and PDF is generated correctly', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $letterType = LetterType::where('code', 'izin_sidang_ta')->first();
    expect($letterType)->not->toBeNull();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'SITI MASITOH',
        'npm' => 'D1A230421',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'target_recipient' => 'HRD PT. Uwu Jump Indonesia',
            'target_city' => 'Tempat',
            'activity_name' => 'Sidang Tugas Akhir',
            'event_day' => 'Kamis',
        ],
    ]);

    $letter = LetterRequest::where('npm', 'D1A230421')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('admin can create Surat Pengantar Magang and PDF is generated with 2 pages and attachment', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $letterType = LetterType::where('code', 'pengantar_magang')->first();
    expect($letterType)->not->toBeNull();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'SYAHRUL MUHAIMIN',
        'npm' => 'D1A230080',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'target_recipient' => 'Kepala Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang',
            'target_agency' => 'Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang',
            'target_city' => 'Tempat',
            'internship_duration' => '5 bulan',
            'start_date' => '14 September 2026',
            'end_date' => '09 Januari 2027',
            'group_members' => [
                ['name' => 'ALVITO BAGASWARA', 'npm' => 'D1A230009'],
            ],
        ],
    ]);

    $letter = LetterRequest::where('npm', 'D1A230080')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(2)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('user can request Surat Pengantar Izin PKL and 1-page PDF is generated with table and narrative', function () {
    $user = User::factory()->create([
        'name' => 'FUTYK CHUSNUL CHOTIMAH',
        'role' => 'mahasiswa',
    ]);

    $letterType = LetterType::where('code', 'pengantar_pkl')->first();
    expect($letterType)->not->toBeNull();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'FUTYK CHUSNUL CHOTIMAH',
        'npm' => 'D1A220013',
        'study_program' => 'Sistem Informasi',
        'address' => 'SUBANG',
        'semester' => 7,
        'academic_year' => '2025/2026',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'target_recipient' => 'Kepala Sekolah SMP Negeri 1 Binong Kabupaten Subang',
            'target_agency' => 'SMP Negeri 1 Binong Kabupaten Subang',
            'target_city' => 'Tempat',
            'internship_duration' => '40 hari',
            'start_date' => '06 Oktober 2025',
            'end_date' => '28 November 2025',
            'group_members' => [
                ['name' => 'SITI NURHALIZA', 'npm' => 'D1A220014', 'address' => 'SUBANG'],
            ],
        ],
    ]);

    $letter = LetterRequest::where('npm', 'D1A220013')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(1)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('user can request Surat Izin Mengikuti Ujian and 2-page PDF is generated with schedule attachment', function () {
    $user = User::factory()->create([
        'name' => 'ANGGI ARIYANTI',
        'role' => 'mahasiswa',
    ]);

    $letterType = LetterType::where('code', 'izin_mengikuti_ujian')->first();
    expect($letterType)->not->toBeNull();

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'ANGGI ARIYANTI',
        'npm' => 'D1A252001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'academic_year' => '2025/2026',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'exam_type' => 'UAS',
            'semester_type' => 'Ganjil',
            'target_recipient' => 'HRD Klinik Pratama Rawat Inap Larasati',
            'target_city' => 'Tempat',
            'exam_dates' => '12-19 Januari 2026',
            'exam_schedules' => [
                [
                    'course_name' => 'Audit Sistem Informasi',
                    'class_name' => '4A',
                    'sks' => 3,
                    'date' => '21-03-2025',
                    'time' => '08.00',
                    'room' => '8',
                ],
            ],
        ],
    ]);

    $letter = LetterRequest::where('npm', 'D1A252001')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.2/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(2)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('user can request Surat Pengantar Ujian Susulan and 1-page PDF is generated with exact official memo format', function () {
    $user = User::factory()->create([
        'name' => 'NURUL FATURROHMA D',
        'role' => 'mahasiswa',
    ]);

    $letterType = LetterType::where('code', 'ujian_susulan')->first();
    expect($letterType)->not->toBeNull()
        ->and($letterType->letter_code)->toBe('PD.1.1/FIK-US');

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'NURUL FATURROHMA D',
        'npm' => 'DIA.220016',
        'study_program' => 'Sistem Informasi',
        'semester' => 3,
        'academic_year' => '2023/2024',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'exam_type' => 'UTS',
            'lecturer_name' => "ACHMAD SYAFA'AT, S.Kom., MT",
            'course_name' => 'Jaringan Komputer',
            'original_score' => '-',
        ],
    ]);

    $letter = LetterRequest::where('npm', 'DIA.220016')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.1/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(1)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('user can request Surat Pengantar Perbaikan Nilai and 1-page PDF is generated with exact official memo format', function () {
    $user = User::factory()->create([
        'name' => 'MOCHAMAD ALKASYAH',
        'role' => 'mahasiswa',
    ]);

    $letterType = LetterType::where('code', 'perbaikan_nilai')->first();
    expect($letterType)->not->toBeNull()
        ->and($letterType->letter_code)->toBe('PD.1.1/FIK-US');

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'MOCHAMAD ALKASYAH',
        'npm' => 'DIA.18.0040',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2025/2026',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'lecturer_name' => 'Ir. Syarif Hidayat, MT',
            'course_name' => 'APSI I',
            'original_score' => 'T',
        ],
    ]);

    $letter = LetterRequest::where('npm', 'DIA.18.0040')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.1/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(1)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('user can request Surat Keterangan Yudisium and 1-page PDF is generated with exact official memo format', function () {
    $user = User::factory()->create([
        'name' => 'HAFIDZ ARIMANSYAH',
        'role' => 'mahasiswa',
    ]);

    $letterType = LetterType::where('code', 'keterangan_yudisium')->first();
    expect($letterType)->not->toBeNull()
        ->and($letterType->letter_code)->toBe('PD.1.3/FIK-US');

    $response = $this->actingAs($user)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'HAFIDZ ARIMANSYAH',
        'npm' => 'D1A220028',
        'study_program' => 'Sistem Informasi',
        'semester' => 8,
        'academic_year' => '2025/2026',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'defense_date_text' => 'Kamis, 27 Agustus 2026',
            'ipk' => '3.73',
            'study_duration' => '4 (Empat) Tahun',
            'defense_grade' => 'A',
            'defense_result' => 'Lulus',
            'yudisium_date_text' => 'Kamis, 27 Agustus 2026',
            'yudisium_predicate' => 'Dengan Pujian',
        ],
    ]);

    $letter = LetterRequest::where('npm', 'D1A220028')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.3/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(1)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('admin can create Surat Undangan Rapat Akademik offline and 1-page PDF is generated with exact official memo format', function () {
    $admin = User::factory()->create([
        'name' => 'Administrator Fasilkom',
        'role' => 'admin',
    ]);

    $letterType = LetterType::where('code', 'undangan_rapat_akademik')->first();
    expect($letterType)->not->toBeNull()
        ->and($letterType->letter_code)->toBe('PD.1.1/FIK-US');

    $response = $this->actingAs($admin)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Bapak Bagus Ali Akbar, S.SI,. M.Kom',
        'npm' => '-',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'agenda' => 'Rapat Akademik',
            'meeting_day_date' => 'Selasa, 8 September 2026',
            'meeting_time' => '09.00 WIB',
            'meeting_type' => 'offline',
            'meeting_location' => 'Ruang 12 Fakultas Ilmu Komputer',
        ],
    ]);

    $letter = LetterRequest::where('student_name', 'Bapak Bagus Ali Akbar, S.SI,. M.Kom')
        ->where('letter_type_id', $letterType->id)
        ->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.1/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(1)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});

test('admin can create Surat Undangan Rapat Akademik online with Google Meet link', function () {
    $admin = User::factory()->create([
        'name' => 'Administrator Fasilkom',
        'role' => 'admin',
    ]);

    $letterType = LetterType::where('code', 'undangan_rapat_akademik')->first();

    $response = $this->actingAs($admin)->post(route('letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Bapak Bagus Ali Akbar, S.SI,. M.Kom',
        'npm' => '-',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
        'signer_id' => 'kaprodi',
        'extra_data' => [
            'agenda' => 'Rapat Akademik',
            'meeting_day_date' => 'Selasa, 8 September 2026',
            'meeting_time' => '09.00 WIB',
            'meeting_type' => 'online',
            'meeting_platform' => 'Google Meet',
            'meeting_link' => 'https://meet.google.com/abc-defg-hij',
        ],
    ]);

    $letter = LetterRequest::where('student_name', 'Bapak Bagus Ali Akbar, S.SI,. M.Kom')
        ->where('letter_type_id', $letterType->id)
        ->latest('id')
        ->first();
    expect($letter)->not->toBeNull();

    $response->assertRedirect(route('letters.show', $letter->uuid));

    expect($letter->letter_number)->toContain('PD.1.1/FIK-US')
        ->and($letter->document_id)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(1)
        ->and(Storage::disk('local')->exists($letter->document->file_path))->toBeTrue();
});
