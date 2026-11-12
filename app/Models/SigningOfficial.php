<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SigningOfficial extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'role_title',
        'signature_title',
        'name',
        'display_name',
        'nip',
        'email',
        'signature_image_path',
        'description',
        'order',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $appends = [
        'signature_image_url',
        'signature_image_base64',
    ];

    public function getSignatureImageUrlAttribute(): ?string
    {
        if ($this->signature_image_path) {
            return asset('storage/'.$this->signature_image_path);
        }

        return null;
    }

    public function getSignatureImageBase64Attribute(): ?string
    {
        if ($this->signature_image_path && Storage::disk('public')->exists($this->signature_image_path)) {
            $path = Storage::disk('public')->path($this->signature_image_path);
            $mime = mime_content_type($path) ?: 'image/png';
            $data = file_get_contents($path);

            return 'data:'.$mime.';base64,'.base64_encode($data);
        }

        return null;
    }

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Scope a query to only include active officials.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to order officials by priority.
     *
     * @param  Builder  $query
     * @return Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order')->orderBy('id');
    }
}
