<?php

namespace App\Services\QuickBooks\Exceptions;

use Exception;

class JPException extends Exception
{
    /**
     * All Exceptions
     * 100 => Parent Customer is not synced with JobProgress.
     * 101 => Parent job is not synced with JobProgress.
     * 102 => Customer not synced.
     * 103 => Job not synced with JobProgress.
     * 104 => Invoice not synced with JobProgress.
     * 105 => Credit Memo not synced with JobProgress.
     * 106 => Payment not synced with JobProgress.
     * 107 => Customer already exists.
     * 108 => Invalid Customer Details.
     * 109 => Duplicate Customer Details.
     * 110 => Payment line not synced with JobProgress.
     * 111 => Parent Job not synced with JobProgress.
     * 112 => Job already exists in JobProgress.
     * 113 => Job details are not valid.
     * 114 => Unable to create customer in JobProgress.
     */

    /** Pass extra information with exception */

    protected $meta = null;

    public function __construct($message = null, $code = 0, Exception $previous = null, $meta = null)
    {
        if (!$message) {

            throw new $this('Unknown ' . get_class($this));
        }

        parent::__construct($message, $code, $previous);

        $this->meta = $meta;
    }

    public function getMeta()
    {
        return $this->meta;
    }
}