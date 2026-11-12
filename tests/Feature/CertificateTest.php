<?php

use App\Models\DigitalCertificate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
});

test('user can view certificates page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('certificates.index'));

    $response->assertOk();
});

test('user can generate a self-signed X.509 digital certificate', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('certificates.store'), [
        'name' => 'PT Solusi Teknologi Bersama',
        'organization' => 'Solusi Teknologi',
        'organizational_unit' => 'Divisi Legal',
        'email' => 'legal@solusiteknologi.com',
        'country' => 'ID',
        'days_valid' => 365,
        'passphrase' => 'Secret123!',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('digital_certificates', [
        'user_id' => $user->id,
        'name' => 'PT Solusi Teknologi Bersama',
        'organization' => 'Solusi Teknologi',
        'country' => 'ID',
        'is_active' => true,
    ]);

    $cert = DigitalCertificate::where('user_id', $user->id)->first();
    expect($cert)->not->toBeNull()
        ->and($cert->serial_number)->not->toBeEmpty()
        ->and($cert->is_default)->toBeTrue()
        ->and(Storage::disk('local')->exists($cert->certificate_path))->toBeTrue()
        ->and(Storage::disk('local')->exists($cert->private_key_path))->toBeTrue();
});

test('user can set certificate as default', function () {
    $user = User::factory()->create();
    $cert1 = DigitalCertificate::factory()->create(['user_id' => $user->id, 'is_default' => true]);
    $cert2 = DigitalCertificate::factory()->create(['user_id' => $user->id, 'is_default' => false]);

    $response = $this->actingAs($user)->post(route('certificates.default', $cert2->id));

    $response->assertRedirect();
    expect($cert1->fresh()->is_default)->toBeFalse();
    expect($cert2->fresh()->is_default)->toBeTrue();
});
