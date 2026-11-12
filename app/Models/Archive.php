<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Archive extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'reference_number',
        'letter_date',
        'letter_type',
        'archive_category_id',
        'incoming_letter_id',
        'letter_request_id',
        'sender',
        'recipient',
        'google_form_url',
        'drive_url',
        'file_path',
        'file_name',
        'file_size',
        'description',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date:Y-m-d',
            'file_size' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ArchiveCategory::class, 'archive_category_id');
    }

    public function incomingLetter(): BelongsTo
    {
        return $this->belongsTo(IncomingLetter::class, 'incoming_letter_id');
    }

    public function letterRequest(): BelongsTo
    {
        return $this->belongsTo(LetterRequest::class, 'letter_request_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

