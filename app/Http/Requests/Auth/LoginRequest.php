<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['nullable', 'string'],
            'email' => ['nullable', 'string'], // Kept for backward compatibility if ever submitted
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Get the login identifier (NPM or username/email).
     */
    public function loginIdentifier(): string
    {
        $raw = $this->filled('login') ? (string) $this->input('login') : (string) $this->input('email', '');

        return trim($raw);
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $identifier = $this->loginIdentifier();
        $password = (string) $this->input('password');

        if ($identifier === '') {
            throw ValidationException::withMessages([
                'login' => 'NPM atau Username wajib diisi.',
            ]);
        }

        // Find user by NPM (case-insensitive) OR username (case-insensitive) OR email (fallback)
        $user = User::where(function ($query) use ($identifier) {
            $query->whereRaw('LOWER(npm) = ?', [strtolower($identifier)])
                ->orWhereRaw('LOWER(username) = ?', [strtolower($identifier)])
                ->orWhereRaw('LOWER(email) = ?', [strtolower($identifier)]);
        })->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($this->throttleKey());

            $errorMessage = trans('auth.failed');
            throw ValidationException::withMessages([
                'login' => $errorMessage,
                'email' => $errorMessage,
            ]);
        }

        Auth::login($user, $this->boolean('remember'));

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        $message = trans('auth.throttle', [
            'seconds' => $seconds,
            'minutes' => ceil($seconds / 60),
        ]);

        throw ValidationException::withMessages([
            'login' => $message,
            'email' => $message,
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->loginIdentifier()).'|'.$this->ip());
    }
}
