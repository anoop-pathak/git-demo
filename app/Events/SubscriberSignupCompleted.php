<?php namespace App\Events;

class SubscriberSignupCompleted
{

    /**
     * Company Model
     */
    public $company;

    function __construct($company, $checkAuthLogin = true)
    {
        $this->company = $company;
        $this->checkAuthLogin = $checkAuthLogin;
    }
}
