<?php

namespace App\Exceptions;

class EmptyBatchException extends \Exception
{
    function __construct() {
        parent::__construct('Batch must contain at least one notification');
    }
}
