<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'cable_segment_id',
    'path',
    'url',
    'caption',
    'mime_type',
    'sort_order',
])]
class CableImage extends Model
{
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function cable(): BelongsTo
    {
        return $this->belongsTo(CableSegment::class, 'cable_segment_id');
    }
}
