<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ArchiveCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'letter_direction',
        'description',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function archives(): HasMany
    {
        return $this->hasMany(Archive::class);
    }
}

