<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceMember extends Model
{
    protected $fillable = [
        'workspace_id',
        'central_user_id',
        'name',
        'email',
        'role_id',
    ];

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
