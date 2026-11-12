<?php

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use App\Services\AcademicLetterService;
use Database\Seeders\LetterTypeSeeder;

beforeEach(function () {
    $this->seed(LetterTypeSeeder::class);
});

test('mahasiswa cannot access letter template management', function () {
    $student = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($student)->get(route('letter-templates.index'));
    $response->assertForbidden();
});

test('admin can view letter templates list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('letter-templates.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LetterTemplates/Index')
            ->has('letterTypes')
        );
});

test('admin can view edit form for a letter template', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::first();

    $response = $this->actingAs($admin)->get(route('letter-templates.edit', $letterType->id));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LetterTemplates/Edit')
            ->has('letterType')
            ->has('placeholders')
        );
});

test('admin can update a letter template narration', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'pengantar_magang')->first();

    $payload = [
        'name' => 'Surat Pengantar Magang BUMN',
        'letter_code' => 'PK.1.1/FIK-US',
        'description' => 'Surat rekomendasi magang kerja ke instansi BUMN dan swasta.',
        'opening_template' => 'Bersama surat ini, kami pimpinan {pejabat_jabatan} memohon kepada Pimpinan {tujuan_instansi} :',
        'content_template' => 'Mahasiswa {nama_mahasiswa} ({npm}) bermaksud magang di {tujuan_instansi} periode {periode_magang}.',
        'closing_template' => 'Demikian kami sampaikan, terima kasih atas kerja samanya.',
        'is_active' => true,
    ];

    $response = $this->actingAs($admin)->put(route('letter-templates.update', $letterType->id), $payload);

    $response->assertRedirect(route('letter-templates.index'));

    $letterType->refresh();
    expect($letterType->name)->toBe('Surat Pengantar Magang BUMN');
    expect($letterType->opening_template)->toContain('{pejabat_jabatan}');
    expect($letterType->content_template)->toContain('{nama_mahasiswa}');
});

test('admin can preview letter template as PDF or JSON base64 for modal viewer', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::first();

    $response = $this->actingAs($admin)->get(route('letter-templates.preview', $letterType->id));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');

    // JSON base64 preview for in-page modal viewer
    $jsonResponse = $this->actingAs($admin)->postJson(route('letter-templates.preview', $letterType->id), [
        'opening_template' => 'Pembuka Uji',
        'content_template' => 'Isi Uji {nama_mahasiswa}',
        'closing_template' => 'Penutup Uji',
        'return_base64' => true,
    ]);

    $jsonResponse->assertOk()
        ->assertJson([
            'success' => true,
        ])
        ->assertJsonStructure([
            'pdfBase64',
            'letterType',
        ]);
});

test('academic letter pdf uses dynamic template variables correctly', function () {
    $letterType = LetterType::where('code', 'pengantar_magang')->first();
    $letterType->update([
        'content_template' => 'Mahasiswa {nama_mahasiswa} ({npm}) jurusan {program_studi} mengajukan magang ke {tujuan_instansi} periode {periode_magang}.',
    ]);

    $student = User::factory()->mahasiswa()->create([
        'name' => 'Ahmad Dahlan',
        'npm' => 'D1A210999',
        'study_program' => 'Sistem Informasi',
    ]);

    $letter = LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $letterType->id,
        'student_name' => $student->name,
        'npm' => $student->npm,
        'study_program' => $student->study_program,
        'semester' => 6,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
        'signer_name' => 'BAGUS ALI AKBAR, S.Si., M.Kom',
        'signer_title' => 'Ka. Program Studi',
        'extra_data' => [
            'company_name' => 'PT Telekomunikasi Indonesia',
            'internship_period' => 'Agustus - September 2026',
        ],
    ]);

    $service = app(AcademicLetterService::class);
    $parsed = $service->parseTemplate($letterType->content_template, $letter);

    expect($parsed)->toContain('Ahmad Dahlan');
    expect($parsed)->toContain('D1A210999');
    expect($parsed)->toContain('PT Telekomunikasi Indonesia');
    expect($parsed)->toContain('Agustus - September 2026');

    // Generating PDF runs without error
    $path = $service->generatePdf($letter);
    expect($path)->not->toBeEmpty();
});

test('admin can preview Surat Keterangan Masih Kuliah PNS template with 1-12 official format', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'keterangan_kuliah_pns')->first();

    $response = $this->actingAs($admin)->postJson(route('letter-templates.preview', $letterType->id), [
        'opening_template' => 'Yang bertanda tangan dibawah ini :',
        'content_template' => 'Nama tersebut tercatat sebagai mahasiswa Fakultas Ilmu Komputer Universitas Subang Program Studi {program_studi} Jenjang Sarjana (S1) Semester {semester_romawi} ({semester_huruf}) pada Tahun Akademik {tahun_akademik}.',
        'closing_template' => 'Demikian keterangan ini dibuat atas permintaan yang bersangkutan untuk dipergunakan seperlunya.',
        'return_base64' => true,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $pdfBase64 = $response->json('pdfBase64');
    expect($pdfBase64)->not->toBeNull();
    $pdfRaw = base64_decode($pdfBase64);
    expect($pdfRaw)->toStartWith('%PDF-');
});

test('admin can preview Surat Pengantar Observasi Mata Kuliah template with group table', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'observasi_matkul')->first();

    $response = $this->actingAs($admin)->postJson(route('letter-templates.preview', $letterType->id), [
        'opening_template' => '{pejabat_jabatan} Fakultas Ilmu Komputer Universitas Subang, menerangkan bahwa :',
        'content_template' => "Bermaksud akan mengadakan observasi Matakuliah {mata_kuliah} untuk penulisan tugas matakuliah yang diikuti oleh mahasiswa tersebut.\n\nSehubungan dengan hal itu kami mohon kiranya Bapak/Ibu dapat memberikan bantuan kepada mahasiswa tersebut diatas, dan mengijinkan pegawai Bapak/Ibu memberikan keterangan/penjelasan untuk penyelesaian tugas matakuliah yang bersangkutan.",
        'closing_template' => 'Demikian permohonan ini kami sampaikan. Atas bantuan dan kesediaannya kami ucapkan terima kasih.',
        'return_base64' => true,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $pdfBase64 = $response->json('pdfBase64');
    expect($pdfBase64)->not->toBeNull();
    $pdfRaw = base64_decode($pdfBase64);
    expect($pdfRaw)->toStartWith('%PDF-');
});

test('admin can preview Surat Pengantar Magang template with 2 pages and attachment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $letterType = LetterType::where('code', 'pengantar_magang')->first();

    $response = $this->actingAs($admin)->postJson(route('letter-templates.preview', $letterType->id), [
        'return_base64' => true,
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'totalPages' => 2,
        ]);

    $pdfBase64 = $response->json('pdfBase64');
    expect($pdfBase64)->not->toBeNull();
    $pdfRaw = base64_decode($pdfBase64);
    expect($pdfRaw)->toStartWith('%PDF-');
});

test('admin can access create new template form', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('letter-templates.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('LetterTemplates/Create')
            ->has('placeholders')
            ->has('predefinedFields')
            ->has('signers')
        );
});

test('admin can preview new unsaved letter template in real time', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->postJson(route('letter-templates.preview-new'), [
        'name' => 'Surat Rekomendasi Lomba Robotik',
        'letter_code' => 'PD.1.2/FIK-US',
        'opening_template' => 'Pimpinan Fakultas menerangkan bahwa:',
        'content_template' => 'Mahasiswa {nama_mahasiswa} ({npm}) didelegasikan dalam kompetisi {nama_lomba} pada instansi {tujuan_instansi}.',
        'closing_template' => 'Demikian rekomendasi ini dibuat untuk dipergunakan semestinya.',
        'custom_sample_values' => [
            'nama_lomba' => 'Kontes Robot Terbang Indonesia',
            'tujuan_instansi' => 'Pusat Prestasi Nasional',
        ],
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
        ]);

    $pdfBase64 = $response->json('pdfBase64');
    expect($pdfBase64)->not->toBeNull();
    $pdfRaw = base64_decode($pdfBase64);
    expect($pdfRaw)->toStartWith('%PDF-');
});

test('admin can store a new letter template with custom required fields', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $payload = [
        'name' => 'Surat Rekomendasi Beasiswa Unggulan',
        'letter_code' => 'PD.1.2/FIK-US',
        'description' => 'Surat rekomendasi resmi untuk pengajuan beasiswa eksternal.',
        'opening_template' => 'Ketua Program Studi {program_studi} Fakultas Ilmu Komputer menerangkan bahwa :',
        'content_template' => 'Mahasiswa {nama_mahasiswa} ({npm}) berprestasi pada bidang {nama_bidang} dan direkomendasikan beasiswa {tujuan_instansi}.',
        'closing_template' => 'Demikian surat rekomendasi ini dibuat dengan sebenarnya.',
        'required_fields' => ['target_agency', 'nama_bidang'],
        'is_active' => true,
    ];

    $response = $this->actingAs($admin)->post(route('letter-templates.store'), $payload);

    $response->assertRedirect(route('letter-templates.index'));

    $created = LetterType::where('name', 'Surat Rekomendasi Beasiswa Unggulan')->first();
    expect($created)->not->toBeNull();
    expect($created->code)->toBe('surat_rekomendasi_beasiswa_unggulan');
    expect($created->required_fields)->toContain('nama_bidang');
    expect($created->required_fields)->toContain('target_agency');
});

test('admin can delete a custom letter template', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $customType = LetterType::create([
        'code' => 'template_uji_hapus',
        'name' => 'Template Uji Hapus',
        'letter_code' => 'UM.1.1/FIK-US',
        'description' => 'Deskripsi uji',
        'opening_template' => 'Opening',
        'content_template' => 'Content',
        'closing_template' => 'Closing',
        'is_active' => true,
    ]);

    $response = $this->actingAs($admin)->delete(route('letter-templates.destroy', $customType->id));

    $response->assertRedirect(route('letter-templates.index'));
    expect(LetterType::find($customType->id))->toBeNull();
});

test('admin deleting template with existing requests deactivates it without force', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->mahasiswa()->create();

    $template = LetterType::create([
        'code' => 'template_dengan_riwayat',
        'name' => 'Template Dengan Riwayat',
        'letter_code' => 'UM.1.1/FIK-US',
        'content_template' => 'Isi surat',
        'is_active' => true,
    ]);

    LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $template->id,
        'student_name' => $student->name,
        'npm' => 'D1A210001',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($admin)->delete(route('letter-templates.destroy', $template->id));

    $response->assertRedirect(route('letter-templates.index'));

    $template->refresh();
    expect($template->is_active)->toBeFalse();
    expect(LetterType::find($template->id))->not->toBeNull();
});

test('admin deleting template with existing requests deletes permanently when force is specified', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->mahasiswa()->create();

    $template = LetterType::create([
        'code' => 'template_force_delete',
        'name' => 'Template Force Delete',
        'letter_code' => 'UM.1.1/FIK-US',
        'content_template' => 'Isi surat',
        'is_active' => true,
    ]);

    LetterRequest::create([
        'user_id' => $student->id,
        'letter_type_id' => $template->id,
        'student_name' => $student->name,
        'npm' => 'D1A210002',
        'study_program' => 'Sistem Informasi',
        'semester' => 4,
        'academic_year' => '2026/2027',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($admin)->delete(route('letter-templates.destroy', $template->id), [
        'force' => 1,
    ]);

    $response->assertRedirect(route('letter-templates.index'));
    expect(LetterType::find($template->id))->toBeNull();
});
