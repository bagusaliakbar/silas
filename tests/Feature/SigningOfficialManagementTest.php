<?php

use App\Models\SigningOfficial;
use App\Models\User;
use App\Services\AcademicLetterService;
use Database\Seeders\SigningOfficialSeeder;

beforeEach(function () {
    $this->seed(SigningOfficialSeeder::class);
});

test('unauthenticated user cannot access signing officials settings', function () {
    $response = $this->get(route('settings.signers.index'));

    $response->assertRedirect(route('login'));
});

test('mahasiswa cannot access signing officials settings and receives 403', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get(route('settings.signers.index'));

    $response->assertForbidden();
});

test('mahasiswa cannot create, update, toggle or delete signing officials', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $signer = SigningOfficial::first();

    // Store attempt
    $this->actingAs($mahasiswa)
        ->post(route('settings.signers.store'), [
            'name' => 'Dr. Haxor',
            'role_title' => 'Rektor',
            'signature_title' => 'Rektor',
        ])
        ->assertForbidden();

    // Update attempt
    $this->actingAs($mahasiswa)
        ->put(route('settings.signers.update', $signer), [
            'name' => 'Changed Name',
            'role_title' => $signer->role_title,
            'signature_title' => $signer->signature_title,
            'code' => $signer->code,
        ])
        ->assertForbidden();

    // Toggle attempt
    $this->actingAs($mahasiswa)
        ->patch(route('settings.signers.toggle', $signer))
        ->assertForbidden();

    // Destroy attempt
    $this->actingAs($mahasiswa)
        ->delete(route('settings.signers.destroy', $signer))
        ->assertForbidden();
});

test('admin can view signing officials list with stats', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('settings.signers.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Signers/Index')
            ->has('signers', 3)
            ->where('stats.total', 3)
            ->where('stats.active', 3)
            ->where('stats.inactive', 0)
        );
});

test('admin can filter signing officials list by search term', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->get(route('settings.signers.index', ['search' => 'Dekan']));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Signers/Index')
            ->has('signers', 2) // Dekan & Wakil Dekan
        );
});

test('admin can create a new signing official', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->post(route('settings.signers.store'), [
        'name' => 'Prof. Dr. H. Moedjiono, M.Sc.',
        'display_name' => 'Prof. Dr. H. Moedjiono, M.Sc.',
        'role_title' => 'Rektor Universitas Subang',
        'signature_title' => 'Rektor',
        'code' => 'rektor',
        'nip' => '196001011985031001',
        'email' => 'rektor@unsub.ac.id',
        'description' => 'Pimpinan tertinggi universitas',
        'order' => 1,
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('signing_officials', [
        'code' => 'rektor',
        'name' => 'Prof. Dr. H. Moedjiono, M.Sc.',
        'signature_title' => 'Rektor',
        'nip' => '196001011985031001',
        'email' => 'rektor@unsub.ac.id',
        'is_active' => true,
    ]);

    // Check that AcademicLetterService dynamically picks up the new signer
    $signers = app(AcademicLetterService::class)->getAvailableSigners();
    expect(collect($signers)->pluck('id'))->toContain('rektor');
});

test('admin can update an existing signing official', function () {
    $admin = User::factory()->create();
    $signer = SigningOfficial::where('code', 'kaprodi')->first();

    $response = $this->actingAs($admin)->put(route('settings.signers.update', $signer), [
        'name' => 'Bagus Ali Akbar, S.Si., M.Kom., Ph.D.',
        'display_name' => 'Bagus Ali Akbar, S.Si., M.Kom., Ph.D.',
        'role_title' => 'Ketua Program Studi Sistem Informasi & TI',
        'signature_title' => 'Ketua Program Studi',
        'code' => 'kaprodi',
        'nip' => '0410019202',
        'email' => 'kaprodi.si@unsub.ac.id',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $signer->refresh();
    expect($signer->name)->toBe('Bagus Ali Akbar, S.Si., M.Kom., Ph.D.')
        ->and($signer->role_title)->toBe('Ketua Program Studi Sistem Informasi & TI');
});

test('admin can toggle active status of a signing official', function () {
    $admin = User::factory()->create();
    $signer = SigningOfficial::where('code', 'wadek')->first();

    // Deactivate
    $response = $this->actingAs($admin)->patch(route('settings.signers.toggle', $signer));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $signer->refresh();
    expect($signer->is_active)->toBeFalse();

    // Activate back
    $response = $this->actingAs($admin)->patch(route('settings.signers.toggle', $signer));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $signer->refresh();
    expect($signer->is_active)->toBeTrue();
});

test('admin cannot deactivate the last active signing official', function () {
    $admin = User::factory()->create();

    // Deactivate all except kaprodi
    SigningOfficial::where('code', '!=', 'kaprodi')->update(['is_active' => false]);

    $lastActive = SigningOfficial::where('code', 'kaprodi')->first();

    $response = $this->actingAs($admin)->patch(route('settings.signers.toggle', $lastActive));
    $response->assertRedirect();
    $response->assertSessionHas('error');

    $lastActive->refresh();
    expect($lastActive->is_active)->toBeTrue();
});

test('admin can delete a signing official when multiple exist', function () {
    $admin = User::factory()->create();
    $signer = SigningOfficial::where('code', 'wadek')->first();

    $response = $this->actingAs($admin)->delete(route('settings.signers.destroy', $signer));
    $response->assertRedirect();
    $response->assertSessionHas('success');

    $this->assertDatabaseMissing('signing_officials', [
        'code' => 'wadek',
    ]);
});

test('admin cannot delete the last remaining signing official', function () {
    $admin = User::factory()->create();

    // Delete down to 1
    SigningOfficial::where('code', '!=', 'kaprodi')->delete();

    $lastSigner = SigningOfficial::where('code', 'kaprodi')->first();

    $response = $this->actingAs($admin)->delete(route('settings.signers.destroy', $lastSigner));
    $response->assertRedirect();
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('signing_officials', [
        'code' => 'kaprodi',
    ]);
});
