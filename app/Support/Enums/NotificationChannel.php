<?php

namespace App\Support\Enums;

enum NotificationChannel: string
{
    case SMS = 'sms';
    case EMAIL = 'email';
    case PUSH = 'push';
}