<?php

namespace App\Models;

use App\Support\PointTypes;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'workspace_id',
    'name',
    'type',
    'types',
    'status',
    'area',
    'latitude',
    'longitude',
    'address',
    'notes',
    'contact_name',
    'contact_phone',
    'port_count',
    'metadata',
    'created_by',
])]
class NetworkPoint extends Model
{
    protected static function booted(): void
    {
        static::saving(function (NetworkPoint $point): void {
            $types = PointTypes::normalize($point->types, $point->type);

            $point->types = $types;
            $point->type = $types[0];
        });
    }

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'port_count' => 'integer',
            'metadata' => 'array',
            'types' => 'array',
        ];
    }

    public function images(): HasMany
    {
        return $this->hasMany(PointImage::class)->orderBy('sort_order');
    }

    public function ports(): HasMany
    {
        return $this->hasMany(NetworkPointPort::class)->orderBy('sort_order')->orderBy('id');
    }

    public function devices(): HasMany
    {
        return $this->hasMany(NetworkPointDevice::class)->orderBy('sort_order')->orderBy('id');
    }

    public function cablesFrom(): HasMany
    {
        return $this->hasMany(CableSegment::class, 'from_point_id');
    }

    public function cablesTo(): HasMany
    {
        return $this->hasMany(CableSegment::class, 'to_point_id');
    }
}
