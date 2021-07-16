<?php
namespace App\QuickBookDesktop\Exceptions;

use App\Services\QuickBookDesktop\Exceptions\JPException;
use Exception;

class InvoiceNotSyncedException extends JPException {

    public function __construct($meta = null, Exception $previous = null, $message = 'Invoice not synced with JobProgress.', $code = 104)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}