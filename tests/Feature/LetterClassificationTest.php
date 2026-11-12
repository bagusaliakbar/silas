<?php

use App\Models\LetterClassification;
use App\Models\User;

test('guest or non-admin cannot access letter classifications', function () {
    $guestResponse = $this->get(route('settings.classifications.index'));
    $guestResponse->assertRedirect(route('login'));

    $mahasiswa = User::factory()->create(['role' => 'mahasiswa']);
    $userResponse = $this->actingAs($mahasiswa)->get(route('settings.classifications.index'));
    $userResponse->assertForbidden();
});

test('admin can view classifications list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('settings.classifications.index'));
    $response->assertOk();
});

test('admin can store, update, toggle, and delete classification', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    // 1. Store
    $storeResponse = $this->actingAs($admin)->post(route('settings.classifications.store'), [
        'code' => 'PD.9.9/FIK-US',
        'name' => 'Klasifikasi Uji Coba',
        'category' => 'Khusus',
        'description' => 'Untuk keperluan testing',
        'order' => 99,
        'is_active' => true,
    ]);

    $storeResponse->assertRedirect();
    $classification = LetterClassification::where('code', 'PD.9.9/FIK-US')->first();
    expect($classification)->not->toBeNull();
    expect($classification->name)->toBe('Klasifikasi Uji Coba');

    // 2. Update
    $updateResponse = $this->actingAs($admin)->put(route('settings.classifications.update', $classification), [
        'code' => 'PD.9.9/FIK-US-REV',
        'name' => 'Klasifikasi Uji Coba Revisi',
        'category' => 'Khusus Revisi',
        'description' => 'Deskripsi revisi',
        'order' => 100,
        'is_active' => true,
    ]);

    $updateResponse->assertRedirect();
    $classification->refresh();
    expect($classification->code)->toBe('PD.9.9/FIK-US-REV');
    expect($classification->name)->toBe('Klasifikasi Uji Coba Revisi');

    // 3. Toggle
    $toggleResponse = $this->actingAs($admin)->patch(route('settings.classifications.toggle', $classification));
    $toggleResponse->assertRedirect();
    $classification->refresh();
    expect($classification->is_active)->toBeFalse();

    // 4. Delete
    $deleteResponse = $this->actingAs($admin)->delete(route('settings.classifications.destroy', $classification));
    $deleteResponse->assertRedirect();
    expect(LetterClassification::where('code', 'PD.9.9/FIK-US-REV')->count())->toBe(0);
});
