<?php
namespace App\Services\QuickBooks\Exceptions;

use App\Services\QuickBooks\Exceptions\JPException;
use Exception;

class ParentCustomerNotSyncedException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Parent Customer is not synced with JobProgress.', $code = 100)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}
