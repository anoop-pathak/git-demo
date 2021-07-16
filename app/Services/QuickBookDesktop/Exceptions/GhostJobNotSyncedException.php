<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class GhostJobNotSyncedException extends JPException {

    public function __construct($meta = null, Exception $previous = null, $message = 'Job not synced with JobProgress.', $code = 103)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}