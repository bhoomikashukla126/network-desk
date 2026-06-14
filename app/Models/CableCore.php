<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CableCore extends Model
{
    protected $fillable = [
        'cable_segment_id',
        'core_number',
        'color',
        'label',
        'status',
    ];

    public function cable(): BelongsTo
    {
        return $this->belongsTo(CableSegment::class, 'cable_segment_id');
    }

    public function ends(): HasMany
    {
        return $this->hasMany(CableCoreEnd::class)->orderBy('side');
    }
}
