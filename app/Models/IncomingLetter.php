<?php

namespace App\Models;

use App\Services\ArchiveService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class IncomingLetter extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'reference_number',
        'sender',
        'subject',
        'letter_date',
        'received_date',
        'category',
        'status',
        'description',
        'drive_url',
        'file_path',
        'file_name',
        'file_size',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'letter_date' => 'date:Y-m-d',
            'received_date' => 'date:Y-m-d',
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

        static::saved(function (self $model): void {
            app(ArchiveService::class)->archiveIncomingLetter($model);
        });

        static::deleting(function (self $model): void {
            $model->archive()?->delete();
        });
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function archive(): HasOne
    {
        return $this->hasOne(Archive::class, 'incoming_letter_id');
    }
}
