<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class JobAlreadyExistException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Job already exists in JobProgress', $code = 112)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}