<?php

namespace App\Exceptions;

use Exception;

class  PhotoLimitExceededException extends Exception
{

    public function __construct($message = "Photo limit exceeded", $code = 4030, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

}
