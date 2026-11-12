<?php

use App\Models\LetterRequest;
use App\Models\LetterType;
use App\Models\User;
use Database\Seeders\LetterTypeSeeder;

test('admin can view admin dashboard with statistics', function () {
    $this->seed(LetterTypeSeeder::class);

    $admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin@esign.test',
    ]);

    $letterType = LetterType::first();

    LetterRequest::create([
        'user_id' => $admin->id,
        'letter_type_id' => $letterType->id,
        'student_name' => 'Bagus Ali Akbar',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'birth_place' => 'Subang',
        'birth_date' => '2000-01-01',
        'address' => 'Subang',
        'semester' => 5,
        'academic_year' => '2026/2027',
        'purpose' => 'Keperluan Beasiswa',
        'status' => 'submitted',
    ]);

    $response = $this->actingAs($admin)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('letterStats')
            ->has('documentStats')
            ->has('certificateStats')
            ->has('pendingLetters')
            ->has('recentLetters')
            ->has('activeSigners')
        );
});

test('root route redirects admin to dashboard', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
    ]);

    $response = $this->actingAs($admin)->get('/');

    $response->assertRedirect(route('dashboard'));
});
