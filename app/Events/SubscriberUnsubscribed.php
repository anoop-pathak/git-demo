<?php

namespace App\Events;

class SubscriberUnsubscribed
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
