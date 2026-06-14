<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NetworkPointPort extends Model
{
    protected $fillable = [
        'network_point_id',
        'network_point_device_id',
        'label',
        'direction',
        'sort_order',
    ];

    public function point(): BelongsTo
    {
        return $this->belongsTo(NetworkPoint::class, 'network_point_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(NetworkPointDevice::class, 'network_point_device_id');
    }

    public function coreEnds(): HasMany
    {
        return $this->hasMany(CableCoreEnd::class);
    }
}
