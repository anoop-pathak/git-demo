<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class CustomerDuplicateException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Duplicate Customer Details.', $code = 109)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}