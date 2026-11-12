<?php

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new users can register', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'npm' => 'D1A230999',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('new mahasiswa can register with npm and study program', function () {
    $response = $this->post('/register', [
        'name' => 'Agus Santoso',
        'npm' => 'd1a230001',
        'study_program' => 'Sistem Informasi',
        'email' => 'agus@unsub.ac.id',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('users', [
        'name' => 'Agus Santoso',
        'npm' => 'D1A230001',
        'study_program' => 'Sistem Informasi',
        'role' => 'mahasiswa',
        'email' => 'agus@unsub.ac.id',
    ]);
});
