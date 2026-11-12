<?php

namespace App\Models;

use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class LetterRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'letter_type_id',
        'document_id',
        'letter_number',
        'student_name',
        'npm',
        'study_program',
        'birth_place',
        'birth_date',
        'address',
        'semester',
        'academic_year',
        'purpose',
        'extra_data',
        'status',
        'signing_mode',
        'manual_signed_file_path',
        'manual_drive_link',
        'can_download_pdf',
        'pickup_note',
        'rejection_reason',
        'signer_name',
        'signer_title',
        'signed_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'semester' => 'integer',
            'can_download_pdf' => 'boolean',
            'extra_data' => 'array',
            'signed_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'can_download_pdf' => false,
    ];

    protected static function booted(): void
    {
        static::creating(function (LetterRequest $letter) {
            if (empty($letter->uuid)) {
                $letter->uuid = (string) Str::uuid();
            }
        });

        static::saved(function (LetterRequest $letter) {
            if ($letter->status === 'signed') {
                app(ArchiveService::class)->archiveLetterRequest($letter);
            }
        });

        static::deleting(function (LetterRequest $letter) {
            $letter->archive()?->delete();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function letterType(): BelongsTo
    {
        return $this->belongsTo(LetterType::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function archive(): HasOne
    {
        return $this->hasOne(Archive::class, 'letter_request_id');
    }

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }
}
