<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class CustomerNotSyncedException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Payment line not synced.', $code = 102)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}
