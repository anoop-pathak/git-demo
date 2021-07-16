<?php
namespace App\Services\QuickBooks\Exceptions;

use App\Services\QuickBooks\Exceptions\JPException;
use Exception;

class PaymentLineNotSyncedException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Payment line not synced.', $code = 110)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}