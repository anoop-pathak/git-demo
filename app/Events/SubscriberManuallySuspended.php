<?php

namespace App\Events;

class SubscriberManuallySuspended
{

    /**
     * Company Model
     */
    public $company;

    function __construct($company)
    {
        $this->company = $company;
    }
}
