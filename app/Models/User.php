<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

use Illuminate\Support\Facades\Storage;

#[Fillable(['name', 'username', 'email', 'avatar', 'password', 'role', 'npm', 'study_program', 'phone', 'birth_place', 'birth_date', 'address', 'semester', 'academic_year'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $appends = ['avatar_url'];

    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return '/storage/'.$this->avatar;
        }

        return null;
    }

    public function isMahasiswa(): bool
    {
        return $this->role === 'mahasiswa';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'pejabat']);
    }

    /**
     * Check whether student has completed their academic profile.
     */
    public function hasCompletedProfile(): bool
    {
        if (! $this->isMahasiswa()) {
            return true;
        }

        return ! empty($this->npm)
            && ! empty($this->phone)
            && ! empty($this->birth_place)
            && ! empty($this->birth_date)
            && ! empty($this->address);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<LetterRequest, $this>
     */
    public function letterRequests(): HasMany
    {
        return $this->hasMany(LetterRequest::class);
    }

    /**
     * @return HasMany<Document, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    /**
     * @return HasMany<DigitalCertificate, $this>
     */
    public function digitalCertificates(): HasMany
    {
        return $this->hasMany(DigitalCertificate::class);
    }

    /**
     * @return HasMany<SignatureField, $this>
     */
    public function signatureFields(): HasMany
    {
        return $this->hasMany(SignatureField::class);
    }
}
