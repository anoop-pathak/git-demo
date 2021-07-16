<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class ParentJobNotSyncedException extends JPException
{
    public function __construct($meta = null, Exception $previous = null, $message = 'Parent Job not synced with JobProgress', $code = 111)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}
