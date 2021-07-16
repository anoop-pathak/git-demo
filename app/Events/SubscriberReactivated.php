<?php

namespace App\Events;

class SubscriberReactivated
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
