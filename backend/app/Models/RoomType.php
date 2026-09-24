<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RoomType extends Model
{
    protected $fillable = [
        'name',
        'base_price',
        'capacity',
        'size',
        'bed_type',
        'description',
        'image',
        'images',
        'is_active',
        'custom_facilities',
    ];

    protected function casts(): array
    {
        return [
            'base_price' => 'decimal:2',
            'capacity' => 'integer',
            'is_active' => 'boolean',
            'images' => 'array',
            'custom_facilities' => 'array',
        ];
    }

    public function facilities(): BelongsToMany
    {
        return $this->belongsToMany(Facility::class, 'room_type_facility');
    }

    public function rooms(): HasMany
    {
        return $this->hasMany(Room::class);
    }
}
