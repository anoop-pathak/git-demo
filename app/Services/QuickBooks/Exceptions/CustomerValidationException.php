<?php
namespace App\Services\QuickBooks\Exceptions;

use App\Services\QuickBooks\Exceptions\JPException;
use Exception;

class CustomerValidationException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Invalid Customer Details.', $code = 108)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}