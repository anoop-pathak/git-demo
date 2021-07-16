<?php
namespace App\Services\QuickBooks\Exceptions;

use App\Services\QuickBooks\Exceptions\JPException;
use Exception;

class ParentJobNotSyncedException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Parent Job not synced with JobProgress', $code = 111)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}
