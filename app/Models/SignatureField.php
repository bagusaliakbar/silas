<?php

namespace App\Models;

use Database\Factories\SignatureFieldFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatureField extends Model
{
    /** @use HasFactory<SignatureFieldFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'document_id',
        'user_id',
        'signer_name',
        'signer_email',
        'role',
        'page_number',
        'pos_x',
        'pos_y',
        'width',
        'height',
        'field_type',
        'signature_type',
        'signature_data',
        'signature_image_path',
        'digital_certificate_id',
        'status',
        'ip_address',
        'user_agent',
        'signed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'page_number' => 'integer',
            'pos_x' => 'float',
            'pos_y' => 'float',
            'width' => 'float',
            'height' => 'float',
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Document, $this>
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
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

    public function isSigned(): bool
    {
        return $this->status === 'signed';
    }
}
