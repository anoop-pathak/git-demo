<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class CustomerNotCreatedException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Unable to create customer in JobProgress.', $code = 114)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}