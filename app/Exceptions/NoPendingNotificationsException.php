<?php

namespace App\Exceptions;

class NoPendingNotificationsException extends \Exception
{
    function __construct(string $batchId) {
        parent::__construct("No pending notifications with batch id {$batchId} found");
    }
}
