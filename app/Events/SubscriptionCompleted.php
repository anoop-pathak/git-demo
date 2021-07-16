<?php namespace App\Events;

class SubscriptionCompleted
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
