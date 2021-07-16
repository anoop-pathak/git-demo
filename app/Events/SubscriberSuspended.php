<?php

namespace App\Events;

class SubscriberSuspended
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
