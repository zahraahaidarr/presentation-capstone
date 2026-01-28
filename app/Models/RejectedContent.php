<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RejectedContent extends Model
{
    protected $fillable = [
        'employee_user_id',
        'content_type',
        'event_id',
        'content',
        'caption',
        'media_path',
        'ai_related',
        'ai_category_id',
        'ai_reason',
        'ai_raw',
        'review_status',
        'reviewed_by',
        'reviewed_at',
        'published_id',
        'published_table',
    ];

    protected $casts = [
        'ai_related' => 'boolean',
        'ai_raw' => 'array',
        'reviewed_at' => 'datetime',
    ];
}
