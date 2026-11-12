<?php

use App\Models\Archive;
use App\Models\ArchiveCategory;
use App\Models\IncomingLetter;
use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('guest or mahasiswa cannot access archives index', function () {
    $guestResponse = $this->get(route('archives.index'));
    $guestResponse->assertRedirect(route('login'));

    $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
    $userResponse = $this->actingAs($mahasiswa)->get(route('archives.index'));
    $userResponse->assertForbidden();
});

test('admin can view archives list with stats and categories', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $category = ArchiveCategory::create([
        'name' => 'Surat Keputusan (SK)',
        'code' => 'SK',
        'letter_direction' => 'both',
        'description' => 'Arsip SK Dekan',
        'color' => 'indigo',
        'is_active' => true,
    ]);

    Archive::create([
        'title' => 'SK Pengangkatan Dosen Tetap 2026',
        'reference_number' => '045/SK/FIK-US/XI/2026',
        'letter_date' => '2026-11-01',
        'letter_type' => 'keluar',
        'archive_category_id' => $category->id,
        'created_by' => $admin->id,
    ]);

    $response = $this->actingAs($admin)->get(route('archives.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Archives/Index')
            ->has('archives.data', 1)
            ->has('categories')
            ->where('stats.total', 1)
            ->where('stats.keluar', 1)
        );
});

test('admin can filter archives by search query and type', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Archive::create([
        'title' => 'Kerjasama Industri PT Telkom',
        'reference_number' => '010/MOU/2026',
        'letter_date' => '2026-11-02',
        'letter_type' => 'keluar',
        'created_by' => $admin->id,
    ]);

    Archive::create([
        'title' => 'Surat Permohonan Hibah Dikti',
        'reference_number' => '099/MASUK/2026',
        'letter_date' => '2026-11-03',
        'letter_type' => 'masuk',
        'created_by' => $admin->id,
    ]);

    // Search query match
    $searchResponse = $this->actingAs($admin)->get(route('archives.index', ['search' => 'Telkom']));
    $searchResponse->assertOk()
        ->assertInertia(fn ($page) => $page->has('archives.data', 1));

    // Type filter match
    $typeResponse = $this->actingAs($admin)->get(route('archives.index', ['type' => 'masuk']));
    $typeResponse->assertOk()
        ->assertInertia(fn ($page) => $page->has('archives.data', 1));
});

test('admin can store an archive with file upload', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);

    $file = UploadedFile::fake()->create('dokumen_akreditasi.pdf', 1024, 'application/pdf');

    $response = $this->actingAs($admin)->post(route('archives.store'), [
        'title' => 'Berkas Akreditasi Prodi SI 2026',
        'reference_number' => 'AKR/001/2026',
        'letter_date' => '2026-11-05',
        'letter_type' => 'lainnya',
        'sender' => 'LAM INFOKOM',
        'recipient' => 'Dekan Fasilkom',
        'description' => 'Dokumen hasil asesmen akreditasi',
        'file' => $file,
    ]);

    $response->assertSessionHas('success');
    $archive = Archive::where('reference_number', 'AKR/001/2026')->first();
    expect($archive)->not->toBeNull();
    expect($archive->file_name)->toBe('dokumen_akreditasi.pdf');
    expect($archive->file_path)->not->toBeNull();
    Storage::disk('local')->assertExists($archive->file_path);
});

test('admin can store an archive with google form or drive url', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->post(route('archives.store'), [
        'title' => 'Formulir Pendaftaran Wisuda Fasilkom',
        'reference_number' => 'FORM/WISUDA/2026',
        'letter_date' => '2026-11-08',
        'letter_type' => 'keluar',
        'google_form_url' => 'https://forms.gle/testWisuda2026',
        'drive_url' => 'https://drive.google.com/drive/folders/testFolderWisuda',
    ]);

    $response->assertSessionHas('success');
    $archive = Archive::where('reference_number', 'FORM/WISUDA/2026')->first();
    expect($archive)->not->toBeNull();
    expect($archive->google_form_url)->toBe('https://forms.gle/testWisuda2026');
    expect($archive->drive_url)->toBe('https://drive.google.com/drive/folders/testFolderWisuda');
});

test('admin can update an archive and replace file', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);

    $oldFile = UploadedFile::fake()->create('lama.pdf', 500, 'application/pdf');
    $this->actingAs($admin)->post(route('archives.store'), [
        'title' => 'Arsip Sebelum Update',
        'reference_number' => 'REV/001/2026',
        'letter_date' => '2026-11-01',
        'letter_type' => 'masuk',
        'file' => $oldFile,
    ]);

    $archive = Archive::where('reference_number', 'REV/001/2026')->first();
    $oldFilePath = $archive->file_path;
    Storage::disk('local')->assertExists($oldFilePath);

    $newFile = UploadedFile::fake()->create('revisi_baru.pdf', 600, 'application/pdf');
    $updateResponse = $this->actingAs($admin)->put(route('archives.update', $archive), [
        'title' => 'Arsip Setelah Update',
        'reference_number' => 'REV/001/2026',
        'letter_date' => '2026-11-02',
        'letter_type' => 'masuk',
        'file' => $newFile,
    ]);

    $updateResponse->assertSessionHas('success');
    $archive->refresh();
    expect($archive->title)->toBe('Arsip Setelah Update');
    expect($archive->file_name)->toBe('revisi_baru.pdf');
    Storage::disk('local')->assertMissing($oldFilePath);
    Storage::disk('local')->assertExists($archive->file_path);
});

test('admin can delete archive and remove stored file', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);

    $file = UploadedFile::fake()->create('hapus.pdf', 200, 'application/pdf');
    $this->actingAs($admin)->post(route('archives.store'), [
        'title' => 'Dokumen Dihapus',
        'letter_date' => '2026-11-01',
        'letter_type' => 'lainnya',
        'file' => $file,
    ]);

    $archive = Archive::first();
    $filePath = $archive->file_path;
    Storage::disk('local')->assertExists($filePath);

    $deleteResponse = $this->actingAs($admin)->delete(route('archives.destroy', $archive));
    $deleteResponse->assertSessionHas('success');

    expect(Archive::count())->toBe(0);
    Storage::disk('local')->assertMissing($filePath);
});

test('admin can download stored archive or redirect to drive url', function () {
    Storage::fake('local');
    $admin = User::factory()->create(['role' => 'admin']);

    // 1. Download local file
    $storedPath = 'archives/test-download.pdf';
    Storage::disk('local')->put($storedPath, '%PDF-1.4 mock content');

    $archiveWithFile = Archive::create([
        'title' => 'Downloadable File',
        'letter_date' => '2026-11-01',
        'letter_type' => 'masuk',
        'file_path' => $storedPath,
        'file_name' => 'test-download.pdf',
        'created_by' => $admin->id,
    ]);

    $downloadResponse = $this->actingAs($admin)->get(route('archives.download', $archiveWithFile));
    $downloadResponse->assertOk();

    // 2. Redirect when only drive url exists
    $archiveWithDrive = Archive::create([
        'title' => 'Drive Only File',
        'letter_date' => '2026-11-01',
        'letter_type' => 'keluar',
        'drive_url' => 'https://drive.google.com/file/d/external-view/view',
        'created_by' => $admin->id,
    ]);

    $driveResponse = $this->actingAs($admin)->get(route('archives.download', $archiveWithDrive));
    $driveResponse->assertRedirect('https://drive.google.com/file/d/external-view/view');
});

test('admin can archive incoming letter directly', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $category = ArchiveCategory::create([
        'name' => 'Surat Masuk Dinas',
        'code' => 'MASUK_DINAS',
        'letter_direction' => 'both',
        'description' => 'Surat masuk dinas',
        'color' => 'emerald',
        'is_active' => true,
    ]);

    $incoming = IncomingLetter::create([
        'reference_number' => '421/088/DISDIK/2026',
        'sender' => 'Dinas Pendidikan',
        'subject' => 'Surat Edaran Libur Semester',
        'letter_date' => '2026-11-01',
        'received_date' => '2026-11-02',
        'category' => 'Edaran',
        'status' => 'received',
    ]);

    $response = $this->actingAs($admin)->post(route('archives.incoming', $incoming));
    $response->assertSessionHas('success');

    $incoming->refresh();
    expect($incoming->status)->toBe('archived');

    $archive = Archive::where('incoming_letter_id', $incoming->id)->first();
    expect($archive)->not->toBeNull();
    expect($archive->title)->toContain('Surat Edaran Libur Semester');
});

test('archive outgoing letter requires signed status', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create(['role' => 'mahasiswa']);

    $letterType = LetterType::create([
        'code' => 'keterangan_aktif',
        'name' => 'Surat Keterangan Mahasiswa Aktif',
        'is_active' => true,
    ]);

    $pendingLetter = LetterRequest::create([
        'user_id' => $user->id,
        'letter_type_id' => $letterType->id,
        'student_name' => $user->name,
        'student_id' => '2022081001',
        'study_program' => 'Sistem Informasi',
        'semester' => 5,
        'purpose' => 'Keperluan beasiswa',
        'status' => 'pending',
    ]);

    // Pending status should be rejected
    $rejectResponse = $this->actingAs($admin)->post(route('archives.outgoing', $pendingLetter));
    $rejectResponse->assertSessionHas('info');
    expect(Archive::where('letter_request_id', $pendingLetter->id)->count())->toBe(0);

    // Update status to signed
    $pendingLetter->update([
        'status' => 'signed',
        'letter_number' => '001/FIK-US/KET/XI/2026',
        'signed_at' => now(),
    ]);

    $category = ArchiveCategory::create([
        'name' => 'Surat Keterangan',
        'code' => 'SURAT_KETERANGAN',
        'letter_direction' => 'keluar',
        'description' => 'Arsip surat keterangan',
        'color' => 'blue',
        'is_active' => true,
    ]);

    $successResponse = $this->actingAs($admin)->post(route('archives.outgoing', $pendingLetter));
    $successResponse->assertSessionHas('success');

    $archive = Archive::where('letter_request_id', $pendingLetter->id)->first();
    expect($archive)->not->toBeNull();
    expect($archive->reference_number)->toBe('001/FIK-US/KET/XI/2026');
});

test('admin can manage archive categories and cannot delete category in use', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // 1. Store category
    $storeResponse = $this->actingAs($admin)->post(route('settings.archive-categories.store'), [
        'name' => 'Surat Tugas Dosen',
        'code' => 'SURAT_TUGAS',
        'letter_direction' => 'keluar',
        'description' => 'Kategori surat tugas pengabdian dan pengajaran',
        'color' => 'purple',
        'is_active' => true,
    ]);

    $storeResponse->assertSessionHas('success');
    $category = ArchiveCategory::where('code', 'SURAT_TUGAS')->first();
    expect($category)->not->toBeNull();

    // 2. Update category
    $updateResponse = $this->actingAs($admin)->put(route('settings.archive-categories.update', $category), [
        'name' => 'Surat Tugas Pengabdian & Pengajaran',
        'code' => 'SURAT_TUGAS',
        'letter_direction' => 'keluar',
        'description' => 'Deskripsi baru',
        'color' => 'indigo',
        'is_active' => true,
    ]);

    $updateResponse->assertSessionHas('success');
    $category->refresh();
    expect($category->name)->toBe('Surat Tugas Pengabdian & Pengajaran');

    // 3. Attach archive to category and attempt delete -> should fail
    Archive::create([
        'title' => 'Surat Tugas KKN 2026',
        'letter_date' => '2026-11-01',
        'letter_type' => 'keluar',
        'archive_category_id' => $category->id,
        'created_by' => $admin->id,
    ]);

    $deleteResponse = $this->actingAs($admin)->delete(route('settings.archive-categories.destroy', $category));
    $deleteResponse->assertSessionHas('error');
    expect(ArchiveCategory::where('code', 'SURAT_TUGAS')->exists())->toBeTrue();

    // 4. Detach archive and delete -> should succeed
    Archive::where('archive_category_id', $category->id)->delete();
    $deleteSuccessResponse = $this->actingAs($admin)->delete(route('settings.archive-categories.destroy', $category));
    $deleteSuccessResponse->assertSessionHas('success');
    expect(ArchiveCategory::where('code', 'SURAT_TUGAS')->exists())->toBeFalse();
});

