<?php
namespace App\Services\QuickBooks\Exceptions;

use App\Services\QuickBooks\Exceptions\JPException;
use Exception;

class CreditMemoNotSyncedException extends JPException {

    public function __construct($meta = null, Exception $previous = null, $message = 'Credit Memo not synced with JobProgress.', $code = 105)
    {
        parent::__construct($message, $code, $previous, $meta);
    }
}