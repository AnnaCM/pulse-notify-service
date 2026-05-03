<?php

namespace App\Exceptions;

class NotificationNotFoundException extends \Exception
{
    function __construct(string $id) {
        parent::__construct("Notification with id {$id} not found");
    }
}
