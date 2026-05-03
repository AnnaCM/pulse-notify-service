<?php

namespace App\Support\Enums;

enum NotificationPriority: string
{
    case HIGH = 'high';
    case NORMAL = 'normal';
    case LOW = 'low';
}
