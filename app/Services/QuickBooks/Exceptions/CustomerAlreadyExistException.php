<?php
namespace App\Services\QuickBooks\Exceptions;

use App\Services\QuickBooks\Exceptions\JPException;
use Exception;

class CustomerAlreadyExistException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Customer already exists.', $code = 107)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}