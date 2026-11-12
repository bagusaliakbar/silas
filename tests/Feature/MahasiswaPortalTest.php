<?php

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
});

test('dashboard route redirects mahasiswa to mahasiswa.dashboard', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get('/dashboard');

    $response->assertRedirect(route('mahasiswa.dashboard'));
});

test('mahasiswa can view their dedicated dashboard', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get(route('mahasiswa.dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Mahasiswa/Dashboard')
            ->has('stats')
            ->has('availableTypes')
            ->has('recentLetters')
        );
});

test('mahasiswa can view pengajuan form', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get(route('mahasiswa.letters.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Mahasiswa/Pengajuan')
            ->has('letterTypes')
            ->has('signers')
        );
});

test('mahasiswa can submit a letter request and redirect to riwayat', function () {
    $mahasiswa = User::factory()->mahasiswa()->create([
        'name' => 'Budi Santoso',
        'npm' => 'D1A260999',
    ]);

    $letterType = LetterType::first();

    $response = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Budi Santoso',
        'npm' => 'D1A260999',
        'study_program' => 'Sistem Informasi',
        'birth_place' => 'Subang',
        'birth_date' => '2005-05-15',
        'address' => 'Jl. Otista No. 10 Subang',
        'semester' => 3,
        'academic_year' => '2026/2027',
        'purpose' => 'Persyaratan Beasiswa Prestasi',
        'signer_id' => 'kaprodi',
    ]);

    $response->assertRedirect(route('mahasiswa.letters.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('letter_requests', [
        'user_id' => $mahasiswa->id,
        'npm' => 'D1A260999',
        'status' => 'submitted',
    ]);
});

test('mahasiswa submits letter without signer_id and admin can determine or adjust signer', function () {
    $mahasiswa = User::factory()->mahasiswa()->create([
        'name' => 'Siti Nurhaliza',
        'npm' => 'D1A260888',
    ]);
    $admin = User::factory()->create(['role' => 'admin']);

    $letterType = LetterType::where('code', 'keterangan_kuliah_non_pns')->first();

    // 1. Mahasiswa applies without signer_id (field removed from student form)
    $response = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Siti Nurhaliza',
        'npm' => 'D1A260888',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
    ]);

    $response->assertRedirect(route('mahasiswa.letters.index'));

    $letter = LetterRequest::where('npm', 'D1A260888')->first();
    expect($letter)->not->toBeNull()
        ->and($letter->status)->toBe('submitted')
        ->and($letter->signer_title)->toBe('Ka. Program Studi');

    // 2. Admin determines/changes the signer to Dekan
    $adminResponse = $this->actingAs($admin)->patch(route('letters.update-signer', $letter->uuid), [
        'signer_id' => 'dekan',
    ]);

    $adminResponse->assertRedirect();
    $letter->refresh();
    expect($letter->signer_title)->toBe('Dekan')
        ->and($letter->signer_name)->toBe('Dr. TEPI PEIRISAL, M.Si');
});

test('mahasiswa can view riwayat pengajuan showing only their own letters', function () {
    $mahasiswa1 = User::factory()->mahasiswa()->create(['npm' => 'D1A111001']);
    $mahasiswa2 = User::factory()->mahasiswa()->create(['npm' => 'D1A222002']);

    $letterType = LetterType::first();

    LetterRequest::create([
        'user_id' => $mahasiswa1->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Mahasiswa Satu',
        'npm' => 'D1A111001',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    LetterRequest::create([
        'user_id' => $mahasiswa2->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Mahasiswa Dua',
        'npm' => 'D1A222002',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($mahasiswa1)->get(route('mahasiswa.letters.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Mahasiswa/Riwayat')
            ->where('letters.total', 1)
            ->where('letters.data.0.student_name', 'Mahasiswa Satu')
        );
});

test('mahasiswa cannot download another student letter', function () {
    $mahasiswa1 = User::factory()->mahasiswa()->create(['npm' => 'D1A111001']);
    $mahasiswa2 = User::factory()->mahasiswa()->create(['npm' => 'D1A222002']);

    $letterType = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $mahasiswa2->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Mahasiswa Dua',
        'npm' => 'D1A222002',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($mahasiswa1)->get(route('mahasiswa.letters.download', $letter->uuid));

    $response->assertForbidden();
});

test('surat tugas mengajar, tugas pembimbing mbkm, dan tugas pembimbing ta do not appear in mahasiswa portal and cannot be requested by mahasiswa', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    // 1. Dashboard does not have tugas_mengajar, tugas_pembimbing_mbkm, or tugas_pembimbing_ta in availableTypes
    $dashResponse = $this->actingAs($mahasiswa)->get(route('mahasiswa.dashboard'));
    $dashResponse->assertOk();
    $availableTypes = $dashResponse->inertiaProps('availableTypes');
    expect(collect($availableTypes)->pluck('code'))
        ->not->toContain('tugas_mengajar')
        ->not->toContain('tugas_pembimbing_mbkm')
        ->not->toContain('tugas_pembimbing_ta')
        ->not->toContain('undangan_rapat_akademik');

    // 2. Pengajuan form does not have them in letterTypes
    $createResponse = $this->actingAs($mahasiswa)->get(route('mahasiswa.letters.create'));
    $createResponse->assertOk();
    $letterTypes = $createResponse->inertiaProps('letterTypes');
    expect(collect($letterTypes)->pluck('code'))
        ->not->toContain('tugas_mengajar')
        ->not->toContain('tugas_pembimbing_mbkm')
        ->not->toContain('tugas_pembimbing_ta')
        ->not->toContain('undangan_rapat_akademik')
        ->toContain('izin_sidang_ta');

    // 3. Submitting restricted letter type via mahasiswa endpoint returns 403 Forbidden
    $tugasType = LetterType::where('code', 'tugas_mengajar')->first();
    $storeResponse1 = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $tugasType->id,
        'student_name' => 'Budi Santoso',
        'npm' => '0410019202',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
    ]);
    $storeResponse1->assertForbidden();

    $mbkmType = LetterType::where('code', 'tugas_pembimbing_mbkm')->first();
    $storeResponse2 = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $mbkmType->id,
        'student_name' => 'Budi Santoso',
        'npm' => '0410019202',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
    ]);
    $storeResponse2->assertForbidden();

    $taType = LetterType::where('code', 'tugas_pembimbing_ta')->first();
    $storeResponse3 = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $taType->id,
        'student_name' => 'Budi Santoso',
        'npm' => '0410019202',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
    ]);
    $storeResponse3->assertForbidden();

    $undanganType = LetterType::where('code', 'undangan_rapat_akademik')->first();
    $storeResponseUndangan = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $undanganType->id,
        'student_name' => 'Bapak Bagus Ali Akbar, S.SI,. M.Kom',
        'npm' => '-',
        'study_program' => 'Sistem Informasi',
        'semester' => 1,
        'academic_year' => '2026/2027',
    ]);
    $storeResponseUndangan->assertForbidden();

    // 4. Mahasiswa CAN submit Surat Keterangan Mengikuti Sidang Tugas Akhir
    $sidangTaType = LetterType::where('code', 'izin_sidang_ta')->first();
    $storeResponse4 = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $sidangTaType->id,
        'student_name' => 'Budi Santoso',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'extra_data' => [
            'target_recipient' => 'HRD PT. Uwu Jump Indonesia',
            'target_city' => 'Tempat',
            'activity_name' => 'Sidang Tugas Akhir',
            'event_day' => 'Kamis',
        ],
    ]);
    $storeResponse4->assertRedirect(route('mahasiswa.letters.index'));
});

test('mahasiswa can edit their own letter application before signed and pdf is regenerated', function () {
    $mahasiswa = User::factory()->mahasiswa()->create(['npm' => 'D1A230421', 'name' => 'Siti Masitoh']);
    $letterType = LetterType::where('code', 'izin_sidang_ta')->first();

    $letter = LetterRequest::create([
        'user_id' => $mahasiswa->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Siti Masitoh',
        'npm' => 'D1A230421',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
        'signer_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'signer_title' => 'Ka. Program Studi',
        'extra_data' => [
            'target_recipient' => 'HRD PT. Lama',
            'target_city' => 'Tempat',
            'activity_name' => 'Sidang Tugas Akhir',
            'event_day' => 'Kamis',
        ],
    ]);

    // 1. Can view edit form
    $editPageResponse = $this->actingAs($mahasiswa)->get(route('mahasiswa.letters.edit', $letter->uuid));
    $editPageResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Mahasiswa/Edit')
            ->has('letter')
            ->has('letterTypes')
        );

    // 2. Can submit update
    $updateResponse = $this->actingAs($mahasiswa)->put(route('mahasiswa.letters.update', $letter->uuid), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Siti Masitoh Updated',
        'npm' => 'D1A230421',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'extra_data' => [
            'target_recipient' => 'HRD PT. Uwu Jump Indonesia Baru',
            'target_city' => 'Subang',
            'activity_name' => 'Sidang Tugas Akhir',
            'event_day' => 'Jum\'at',
        ],
    ]);

    $updateResponse->assertRedirect(route('mahasiswa.letters.index'));

    $letter->refresh();
    expect($letter->student_name)->toBe('Siti Masitoh Updated')
        ->and($letter->extra_data['target_recipient'])->toBe('HRD PT. Uwu Jump Indonesia Baru')
        ->and($letter->extra_data['event_day'])->toBe('Jum\'at');
});

test('mahasiswa cannot edit another student letter or a signed letter', function () {
    $mahasiswa1 = User::factory()->mahasiswa()->create(['npm' => 'D1A230001']);
    $mahasiswa2 = User::factory()->mahasiswa()->create(['npm' => 'D1A230002']);
    $letterType = LetterType::first();

    $letter1 = LetterRequest::create([
        'user_id' => $mahasiswa1->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Student 1',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $letterSigned = LetterRequest::create([
        'user_id' => $mahasiswa1->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Student 1',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
        'status' => 'signed',
    ]);

    // Forbidden for another student
    $this->actingAs($mahasiswa2)->get(route('mahasiswa.letters.edit', $letter1->uuid))->assertForbidden();
    $this->actingAs($mahasiswa2)->put(route('mahasiswa.letters.update', $letter1->uuid), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Hacked',
        'npm' => 'D1A230002',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
    ])->assertForbidden();

    // Cannot edit signed letter
    $this->actingAs($mahasiswa1)->get(route('mahasiswa.letters.edit', $letterSigned->uuid))->assertRedirect(route('mahasiswa.letters.index'));
    $this->actingAs($mahasiswa1)->put(route('mahasiswa.letters.update', $letterSigned->uuid), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'Changed',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
    ])->assertRedirect(route('mahasiswa.letters.index'));
});

test('mahasiswa can cancel/delete their own letter application before signed', function () {
    $mahasiswa = User::factory()->mahasiswa()->create(['npm' => 'D1A230099']);
    $letterType = LetterType::first();

    $letter = LetterRequest::create([
        'user_id' => $mahasiswa->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Mahasiswa Test',
        'npm' => 'D1A230099',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($mahasiswa)->delete(route('mahasiswa.letters.destroy', $letter->uuid));

    $response->assertRedirect(route('mahasiswa.letters.index'));
    expect(LetterRequest::find($letter->id))->toBeNull();
});

test('mahasiswa cannot cancel/delete another student letter or a signed letter', function () {
    $mahasiswa1 = User::factory()->mahasiswa()->create(['npm' => 'D1A230001']);
    $mahasiswa2 = User::factory()->mahasiswa()->create(['npm' => 'D1A230002']);
    $letterType = LetterType::first();

    $letter1 = LetterRequest::create([
        'user_id' => $mahasiswa1->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Student 1',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $letterSigned = LetterRequest::create([
        'user_id' => $mahasiswa1->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Student 1',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'semester' => 2,
        'academic_year' => '2026/2027',
        'status' => 'signed',
    ]);

    // Forbidden for another student
    $this->actingAs($mahasiswa2)->delete(route('mahasiswa.letters.destroy', $letter1->uuid))->assertForbidden();
    expect(LetterRequest::find($letter1->id))->not->toBeNull();

    // Cannot delete signed letter
    $this->actingAs($mahasiswa1)->delete(route('mahasiswa.letters.destroy', $letterSigned->uuid))->assertRedirect(route('mahasiswa.letters.index'));
    expect(LetterRequest::find($letterSigned->id))->not->toBeNull();
});

test('mahasiswa can submit Surat Pengantar Magang and update with group members', function () {
    $mahasiswa = User::factory()->mahasiswa()->create([
        'name' => 'SYAHRUL MUHAIMIN',
        'npm' => 'D1A230080',
        'study_program' => 'Sistem Informasi',
    ]);

    $letterType = LetterType::where('code', 'pengantar_magang')->first();
    expect($letterType)->not->toBeNull();

    // 1. Submit pengajuan
    $response = $this->actingAs($mahasiswa)->post(route('mahasiswa.letters.store'), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'SYAHRUL MUHAIMIN',
        'npm' => 'D1A230080',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
        'extra_data' => [
            'target_recipient' => 'Kepala Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang',
            'target_agency' => 'Kantor Urusan Agama (KUA) Kecamatan Ciater Kabupaten Subang',
            'target_city' => 'Tempat',
            'internship_duration' => '5 bulan',
            'start_date' => '14 September 2026',
            'end_date' => '09 Januari 2027',
            'group_members' => [],
        ],
    ]);

    $response->assertRedirect(route('mahasiswa.letters.index'));

    $letter = LetterRequest::where('npm', 'D1A230080')->where('letter_type_id', $letterType->id)->first();
    expect($letter)->not->toBeNull()
        ->and($letter->document->total_pages)->toBe(2);

    // 2. Edit pengajuan to add a group member
    $updateResponse = $this->actingAs($mahasiswa)->put(route('mahasiswa.letters.update', $letter->uuid), [
        'letter_type_id' => $letterType->id,
        'student_name' => 'SYAHRUL MUHAIMIN',
        'npm' => 'D1A230080',
        'study_program' => 'Sistem Informasi',
        'semester' => 7,
        'academic_year' => '2026/2027',
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

    $updateResponse->assertRedirect(route('mahasiswa.letters.index'));

    $letter->refresh();
    expect($letter->extra_data['group_members'])->toHaveCount(1)
        ->and($letter->extra_data['group_members'][0]['name'])->toBe('ALVITO BAGASWARA')
        ->and($letter->document->total_pages)->toBe(2);
});
