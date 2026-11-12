<?php

use App\Models\IncomingLetter;
use App\Models\User;

test('guest or non-admin cannot access incoming letters', function () {
    $guestResponse = $this->get(route('incoming-letters.index'));
    $guestResponse->assertRedirect(route('login'));

    $user = User::factory()->create(['role' => 'mahasiswa']);
    $userResponse = $this->actingAs($user)->get(route('incoming-letters.index'));
    $userResponse->assertForbidden();
});

test('admin can access incoming letters list', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get(route('incoming-letters.index'));
    $response->assertOk();
});

test('admin can store, update, and delete an incoming letter with google drive url', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $storeResponse = $this->actingAs($admin)->post(route('incoming-letters.store'), [
        'reference_number' => '421/001/DISDIK/2026',
        'sender' => 'Dinas Pendidikan Kabupaten Subang',
        'subject' => 'Undangan Rapat Koordinasi MBKM',
        'letter_date' => '2026-11-01',
        'received_date' => '2026-11-02',
        'category' => 'Undangan',
        'status' => 'received',
        'description' => 'Rapat koordinasi hari Kamis',
        'drive_url' => 'https://drive.google.com/file/d/1a2b3c4d5e/view?usp=sharing',
    ]);

    $storeResponse->assertRedirect(route('incoming-letters.index'));

    $letter = IncomingLetter::first();
    expect($letter)->not->toBeNull();
    expect($letter->reference_number)->toBe('421/001/DISDIK/2026');
    expect($letter->drive_url)->toBe('https://drive.google.com/file/d/1a2b3c4d5e/view?usp=sharing');

    // Update
    $updateResponse = $this->actingAs($admin)->put(route('incoming-letters.update', $letter), [
        'reference_number' => '421/001/DISDIK/2026-REV',
        'sender' => 'Dinas Pendidikan Jabar',
        'subject' => 'Undangan Rapat Koordinasi MBKM Wilayah',
        'letter_date' => '2026-11-01',
        'received_date' => '2026-11-02',
        'category' => 'Undangan',
        'status' => 'disposed',
        'description' => 'Update agenda rapat',
        'drive_url' => 'https://drive.google.com/file/d/9z8y7x6w5v/view?usp=sharing',
    ]);

    $updateResponse->assertRedirect(route('incoming-letters.index'));
    $letter->refresh();
    expect($letter->reference_number)->toBe('421/001/DISDIK/2026-REV');
    expect($letter->drive_url)->toBe('https://drive.google.com/file/d/9z8y7x6w5v/view?usp=sharing');

    // Delete
    $deleteResponse = $this->actingAs($admin)->delete(route('incoming-letters.destroy', $letter));
    $deleteResponse->assertRedirect(route('incoming-letters.index'));

    expect(IncomingLetter::count())->toBe(0);
});
