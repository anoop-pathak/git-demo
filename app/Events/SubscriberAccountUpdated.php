<?php namespace App\Events;

class SubscriberAccountUpdated
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
