<?php

use App\Models\Course;
use App\Models\User;

test('unauthenticated user cannot access courses management and is redirected to login', function () {
    $response = $this->get(route('settings.courses.index'));

    $response->assertRedirect(route('login'));
});

test('mahasiswa cannot access courses management and receives 403 forbidden', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();

    $response = $this->actingAs($mahasiswa)->get(route('settings.courses.index'));

    $response->assertForbidden();
});

test('mahasiswa cannot store, update, toggle or delete courses', function () {
    $mahasiswa = User::factory()->mahasiswa()->create();
    $course = Course::factory()->create([
        'code' => 'D1A11001',
        'name' => 'Algoritma & Pemrograman',
    ]);

    // Store attempt
    $this->actingAs($mahasiswa)
        ->post(route('settings.courses.store'), [
            'code' => 'D1A99999',
            'name' => 'Mata Kuliah Ilegal',
            'sks' => 3,
        ])
        ->assertForbidden();

    // Update attempt
    $this->actingAs($mahasiswa)
        ->put(route('settings.courses.update', $course), [
            'code' => 'D1A11001',
            'name' => 'Nama Baru Dihack',
            'sks' => 4,
        ])
        ->assertForbidden();

    // Toggle attempt
    $this->actingAs($mahasiswa)
        ->patch(route('settings.courses.toggle', $course))
        ->assertForbidden();

    // Destroy attempt
    $this->actingAs($mahasiswa)
        ->delete(route('settings.courses.destroy', $course))
        ->assertForbidden();
});

test('admin can view courses list with pagination and stats', function () {
    $admin = User::factory()->create();

    Course::factory()->create([
        'code' => 'IF101',
        'name' => 'Dasar Pemrograman',
        'sks' => 3,
        'is_active' => true,
    ]);
    Course::factory()->create([
        'code' => 'IF102',
        'name' => 'Struktur Data',
        'sks' => 3,
        'is_active' => false,
    ]);

    $response = $this->actingAs($admin)->get(route('settings.courses.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Settings/Courses/Index')
            ->has('courses.data', 2)
            ->where('stats.total', 2)
            ->where('stats.active', 1)
            ->where('stats.inactive', 1)
            ->where('stats.total_sks', 6)
        );
});

test('admin can filter courses by search term and study program', function () {
    $admin = User::factory()->create();

    Course::factory()->create([
        'code' => 'SI101',
        'name' => 'Konsep Sistem Informasi',
        'study_program' => 'Sistem Informasi',
    ]);
    Course::factory()->create([
        'code' => 'TI201',
        'name' => 'Jaringan Komputer',
        'study_program' => 'Teknik Informatika',
    ]);

    // Search by code/name
    $searchResponse = $this->actingAs($admin)->get(route('settings.courses.index', [
        'search' => 'Konsep',
    ]));
    $searchResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.code', 'SI101')
        );

    // Filter by study program
    $prodiResponse = $this->actingAs($admin)->get(route('settings.courses.index', [
        'study_program' => 'Teknik Informatika',
    ]));
    $prodiResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('courses.data', 1)
            ->where('courses.data.0.code', 'TI201')
        );
});

test('admin can store a new course with validation', function () {
    $admin = User::factory()->create();

    $response = $this->actingAs($admin)->post(route('settings.courses.store'), [
        'code' => 'D1A22010',
        'name' => 'Rekayasa Perangkat Lunak',
        'sks' => 3,
        'semester' => 4,
        'study_program' => 'Sistem Informasi',
        'description' => 'Mata kuliah rekayasa software modern',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('settings.courses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('courses', [
        'code' => 'D1A22010',
        'name' => 'Rekayasa Perangkat Lunak',
        'sks' => 3,
        'semester' => 4,
        'is_active' => true,
    ]);
});

test('admin cannot store course with duplicate code', function () {
    $admin = User::factory()->create();

    Course::factory()->create(['code' => 'DUPLICATE01']);

    $response = $this->actingAs($admin)->post(route('settings.courses.store'), [
        'code' => 'DUPLICATE01',
        'name' => 'Mata Kuliah Lain',
        'sks' => 2,
    ]);

    $response->assertSessionHasErrors('code');
});

test('admin can update an existing course', function () {
    $admin = User::factory()->create();
    $course = Course::factory()->create([
        'code' => 'OLD101',
        'name' => 'Nama Lama',
        'sks' => 2,
    ]);

    $response = $this->actingAs($admin)->put(route('settings.courses.update', $course), [
        'code' => 'NEW101',
        'name' => 'Nama Baru Direvisi',
        'sks' => 3,
        'semester' => 5,
        'study_program' => 'Sistem Informasi',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('settings.courses.index'))
        ->assertSessionHas('success');

    $course->refresh();
    expect($course->code)->toBe('NEW101')
        ->and($course->name)->toBe('Nama Baru Direvisi')
        ->and($course->sks)->toBe(3);
});

test('admin can toggle active status of a course', function () {
    $admin = User::factory()->create();
    $course = Course::factory()->create(['is_active' => true]);

    $response = $this->actingAs($admin)->patch(route('settings.courses.toggle', $course));

    $response->assertRedirect();
    expect($course->fresh()->is_active)->toBeFalse();

    $this->actingAs($admin)->patch(route('settings.courses.toggle', $course));
    expect($course->fresh()->is_active)->toBeTrue();
});

test('admin can delete a course', function () {
    $admin = User::factory()->create();
    $course = Course::factory()->create();

    $response = $this->actingAs($admin)->delete(route('settings.courses.destroy', $course));

    $response->assertRedirect(route('settings.courses.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('courses', [
        'id' => $course->id,
    ]);
});

test('admin can export courses data to excel stream download', function () {
    $admin = User::factory()->create();
    Course::factory()->count(3)->create();

    $response = $this->actingAs($admin)->get(route('settings.courses.export'));

    $response->assertOk();
    expect($response->headers->get('content-type'))
        ->toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

