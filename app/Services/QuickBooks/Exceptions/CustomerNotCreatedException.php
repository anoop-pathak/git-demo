<?php
namespace App\Services\QuickBooks\Exceptions;

use App\Services\QuickBooks\Exceptions\JPException;
use Exception;

class CustomerNotCreatedException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Unable to create customer in JobProgress.', $code = 114)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}