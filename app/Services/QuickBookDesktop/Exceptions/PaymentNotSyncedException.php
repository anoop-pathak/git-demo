<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class PaymentNotSyncedException extends JPException {

    public function __construct($meta = null, Exception $previous = null, $message = 'Payment not synced with JobProgress.', $code = 106)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}