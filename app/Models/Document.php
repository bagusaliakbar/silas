<?php

namespace App\Models;

use Database\Factories\DocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Document extends Model
{
    /** @use HasFactory<DocumentFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'title',
        'original_filename',
        'file_path',
        'signed_file_path',
        'file_size',
        'total_pages',
        'sha256_original',
        'sha256_signed',
        'status',
        'signing_method',
        'psre_provider',
        'digital_certificate_id',
        'verification_token',
        'metadata',
        'signed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'signed_at' => 'datetime',
            'file_size' => 'integer',
            'total_pages' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Document $document): void {
            if (empty($document->uuid)) {
                $document->uuid = (string) Str::uuid();
            }
            if (empty($document->verification_token)) {
                $document->verification_token = Str::random(32);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<DigitalCertificate, $this>
     */
    public function digitalCertificate(): BelongsTo
    {
        return $this->belongsTo(DigitalCertificate::class);
    }

    /**
     * @return HasMany<SignatureField, $this>
     */
    public function signatureFields(): HasMany
    {
        return $this->hasMany(SignatureField::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class)->orderBy('created_at', 'desc');
    }

    /**
     * @return HasOne<LetterRequest, $this>
     */
    public function letterRequest(): HasOne
    {
        return $this->hasOne(LetterRequest::class, 'document_id');
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isAccessibleBy(User $user): bool
    {
        if ($this->user_id === $user->id) {
            return true;
        }

        if ($user->isAdmin()) {
            return true;
        }

        return $this->signatureFields()
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhere('signer_email', $user->email);
            })
            ->exists();
    }
}
