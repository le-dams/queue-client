<?php

namespace QueueClient\Exception;

use Throwable;

class QueueServerException extends \Exception
{
    public function __construct($message = "Queue server return an error", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
