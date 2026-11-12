<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'npm' => 'required|string|max:50|unique:'.User::class.',npm',
            'study_program' => 'nullable|string|max:100',
            'email' => 'required|string|lowercase|email|max:255|unique:'.User::class,
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ], [
            'npm.required' => 'NPM (Nomor Pokok Mahasiswa) wajib diisi untuk registrasi akun mahasiswa.',
            'npm.unique' => 'NPM ini sudah terdaftar dalam sistem.',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'npm' => strtoupper(trim($validated['npm'])),
            'study_program' => ! empty($validated['study_program']) ? $validated['study_program'] : 'Sistem Informasi',
            'role' => 'mahasiswa',
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('profile.edit')
            ->with('warning', 'Selamat datang di Sistem Persuratan! Silakan lengkapi biodata akademik Anda terlebih dahulu agar dapat mengajukan surat.');
    }
}
