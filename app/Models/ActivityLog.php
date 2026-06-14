<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'workspace_id',
        'central_user_id',
        'workspace_member_id',
        'actor_name',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
