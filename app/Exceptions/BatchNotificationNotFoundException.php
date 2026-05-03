<?php

namespace App\Exceptions;

class BatchNotificationNotFoundException extends \Exception
{
    function __construct(string $batchId) {
        parent::__construct("Batch with id {$batchId} not found");
    }
}
