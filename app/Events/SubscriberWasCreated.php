<?php namespace App\Events;

class SubscriberWasCreated
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
