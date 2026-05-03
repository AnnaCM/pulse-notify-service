<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notification extends Model
{
    use HasUuids, HasFactory;

    protected $fillable = [
        'id',
        'batch_id',
        'channel',
        'recipient',
        'content',
        'status',
        'priority',
        'attempts',
        'scheduled_at',
        'sent_at',
        'external_id',
        'idempotency_key',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];
}
