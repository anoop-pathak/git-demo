<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class CustomerAlreadyExistException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Customer already exists.', $code = 107)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}