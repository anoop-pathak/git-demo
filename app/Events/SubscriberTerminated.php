<?php

namespace App\Events;

class SubscriberTerminated
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
