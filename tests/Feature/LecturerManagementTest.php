<?php

use App\Models\Lecturer;
use App\Models\User;

test('guest or mahasiswa cannot access lecturers index', function () {
    $guestResponse = $this->get(route('settings.lecturers.index'));
    $guestResponse->assertRedirect(route('login'));

    $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
    $userResponse = $this->actingAs($mahasiswa)->get(route('settings.lecturers.index'));
    $userResponse->assertForbidden();
});

test('mahasiswa cannot perform lecturer crud or toggle', function () {
    $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
    $lecturer = Lecturer::create([
        'name' => 'Dr. H. Ahmad Fauzi, M.Kom.',
        'nidn' => '0412058001',
        'is_active' => true,
    ]);

    // Store attempt
    $this->actingAs($mahasiswa)
        ->post(route('settings.lecturers.store'), ['name' => 'Illegal Store'])
        ->assertForbidden();

    // Update attempt
    $this->actingAs($mahasiswa)
        ->put(route('settings.lecturers.update', $lecturer), ['name' => 'Illegal Update'])
        ->assertForbidden();

    // Toggle attempt
    $this->actingAs($mahasiswa)
        ->patch(route('settings.lecturers.toggle', $lecturer))
        ->assertForbidden();

    // Destroy attempt
    $this->actingAs($mahasiswa)
        ->delete(route('settings.lecturers.destroy', $lecturer))
        ->assertForbidden();
});

test('admin can view lecturers list with stats', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Lecturer::create([
        'name' => 'Budi Santoso, M.Kom.',
        'nidn' => '0401018501',
        'nip' => '198501012010121001',
        'functional_position' => 'Lektor',
        'academic_rank' => 'Penata / IIIc',
        'study_program' => 'Sistem Informasi',
        'is_active' => true,
    ]);

    Lecturer::create([
        'name' => 'Citra Dewi, M.T.',
        'nidn' => '0402028802',
        'functional_position' => 'Asisten Ahli',
        'study_program' => 'Teknik Informatika',
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('settings.lecturers.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Lecturers/Index')
            ->has('lecturers.data', 2)
            ->where('stats.total', 2)
            ->where('stats.active', 1)
            ->where('stats.inactive', 1)
        );
});

test('admin can search and filter lecturers', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Lecturer::create([
        'name' => 'Prof. Dr. Ir. Gunawan Wibisono',
        'nidn' => '0011223344',
        'functional_position' => 'Guru Besar',
        'study_program' => 'Ilmu Komputer',
        'is_active' => true,
    ]);

    Lecturer::create([
        'name' => 'Eka Prasetya, M.Kom.',
        'nidn' => '0405068901',
        'functional_position' => 'Tenaga Pengajar',
        'study_program' => 'Sistem Informasi',
        'is_active' => true,
    ]);

    // 1. Search by name query
    $searchResponse = $this->actingAs($admin)->get(route('settings.lecturers.index', ['search' => 'Gunawan']));
    $searchResponse->assertOk()
        ->assertInertia(fn ($page) => $page->has('lecturers.data', 1));

    // 2. Filter by functional position
    $positionResponse = $this->actingAs($admin)->get(route('settings.lecturers.index', ['functional_position' => 'Guru Besar']));
    $positionResponse->assertOk()
        ->assertInertia(fn ($page) => $page->has('lecturers.data', 1));
});

test('admin can store a new lecturer with validation', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // Validation failure: missing name
    $invalidResponse = $this->actingAs($admin)->post(route('settings.lecturers.store'), [
        'name' => '',
        'nidn' => '0412345678',
    ]);
    $invalidResponse->assertSessionHasErrors(['name']);

    // Valid submission
    $storeResponse = $this->actingAs($admin)->post(route('settings.lecturers.store'), [
        'name' => 'Dr. Hendra Wijaya, S.Kom., M.T.',
        'nidn' => '0415087702',
        'nip' => '197708152005011002',
        'functional_position' => 'Lektor Kepala',
        'academic_rank' => 'Pembina / IVa',
        'study_program' => 'Sistem Informasi',
        'email' => 'hendra.wijaya@fasilkom.unsub.ac.id',
        'phone' => '081234567890',
        'is_active' => true,
    ]);

    $storeResponse->assertSessionHas('success');

    $lecturer = Lecturer::where('nidn', '0415087702')->first();
    expect($lecturer)->not->toBeNull();
    expect($lecturer->name)->toBe('Dr. Hendra Wijaya, S.Kom., M.T.');
    expect($lecturer->functional_position)->toBe('Lektor Kepala');
    expect($lecturer->is_active)->toBeTrue();
});

test('admin can update, toggle active status, and delete lecturer', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $lecturer = Lecturer::create([
        'name' => 'Indra Permana, M.Kom.',
        'nidn' => '0420048801',
        'functional_position' => 'Tenaga Pengajar',
        'is_active' => true,
    ]);

    // 1. Update
    $updateResponse = $this->actingAs($admin)->put(route('settings.lecturers.update', $lecturer), [
        'name' => 'Indra Permana, S.T., M.Kom.',
        'nidn' => '0420048801',
        'functional_position' => 'Asisten Ahli',
        'academic_rank' => 'Penata Muda / IIIa',
        'study_program' => 'Teknik Informatika',
        'is_active' => true,
    ]);

    $updateResponse->assertSessionHas('success');
    $lecturer->refresh();
    expect($lecturer->name)->toBe('Indra Permana, S.T., M.Kom.');
    expect($lecturer->functional_position)->toBe('Asisten Ahli');

    // 2. Toggle status
    $toggleResponse = $this->actingAs($admin)->patch(route('settings.lecturers.toggle', $lecturer));
    $toggleResponse->assertSessionHas('success');
    $lecturer->refresh();
    expect($lecturer->is_active)->toBeFalse();

    // 3. Delete
    $deleteResponse = $this->actingAs($admin)->delete(route('settings.lecturers.destroy', $lecturer));
    $deleteResponse->assertSessionHas('success');
    expect(Lecturer::find($lecturer->id))->toBeNull();
});

test('lecturers search endpoint returns active lecturers for autocomplete', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    Lecturer::create([
        'name' => 'Rahmat Hidayat, M.Cs.',
        'nidn' => '0409099001',
        'functional_position' => 'Lektor',
        'study_program' => 'Teknik Informatika',
        'is_active' => true,
    ]);

    Lecturer::create([
        'name' => 'Rahmat Non-Aktif, M.Kom.',
        'nidn' => '0409099002',
        'functional_position' => 'Tenaga Pengajar',
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('settings.lecturers.search', ['query' => 'Rahmat']));

    $response->assertOk()
        ->assertJsonCount(1)
        ->assertJsonFragment(['name' => 'Rahmat Hidayat, M.Cs.'])
        ->assertJsonMissing(['name' => 'Rahmat Non-Aktif, M.Kom.']);
});

