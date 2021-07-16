<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class JobValidationException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Job details are not valid', $code = 113)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}