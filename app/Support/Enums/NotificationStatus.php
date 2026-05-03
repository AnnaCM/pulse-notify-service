<?php

namespace App\Support\Enums;

enum NotificationStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SENT = 'sent';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}
