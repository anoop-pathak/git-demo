<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class CustomerValidationException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Invalid Customer Details.', $code = 108)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}