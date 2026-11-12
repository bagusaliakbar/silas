<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LetterType extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'description',
        'letter_code',
        'opening_template',
        'content_template',
        'closing_template',
        'required_fields',
        'template_view',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'required_fields' => 'array',
            'is_active' => 'boolean',
            'order' => 'integer',
        ];
    }

    public function letterRequests(): HasMany
    {
        return $this->hasMany(LetterRequest::class);
    }
}
