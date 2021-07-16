<?php
namespace App\Services\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class PaymentMethodNotSyncedException extends JPException {

    public function __construct($meta = null, Exception $previous = null, $message = 'Payment method not synced with JobProgress.', $code = 115)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}