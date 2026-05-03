<?php

namespace App\Exceptions;

class CannotCancelNotificationException extends \Exception
{
    function __construct() {
        parent::__construct('Only pending notifications can be cancelled');
    }
}
