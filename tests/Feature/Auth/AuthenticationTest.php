<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('mahasiswa can authenticate using NPM', function () {
    $mahasiswa = User::factory()->mahasiswa()->create([
        'npm' => 'D1A230001',
        'password' => bcrypt('password123'),
    ]);

    // Test with lowercase npm as well to verify case-insensitivity
    $response = $this->post('/login', [
        'login' => 'd1a230001',
        'password' => 'password123',
    ]);

    $this->assertAuthenticatedAs($mahasiswa);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('admin can authenticate using username', function () {
    $admin = User::factory()->create([
        'username' => 'admin',
        'role' => 'admin',
        'password' => bcrypt('adminpass'),
    ]);

    $response = $this->post('/login', [
        'login' => 'admin',
        'password' => 'adminpass',
    ]);

    $this->assertAuthenticatedAs($admin);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can authenticate using email for backward compatibility', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('password123'),
    ]);

    $response = $this->post('/login', [
        'login' => 'user@example.com',
        'password' => 'password123',
    ]);

    $this->assertAuthenticatedAs($user);
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $mahasiswa = User::factory()->mahasiswa()->create([
        'npm' => 'D1A230002',
        'password' => bcrypt('password123'),
    ]);

    $this->post('/login', [
        'login' => 'D1A230002',
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can not authenticate with nonexistent npm or username', function () {
    $this->post('/login', [
        'login' => 'NONEXISTENT_USER',
        'password' => 'password123',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});
