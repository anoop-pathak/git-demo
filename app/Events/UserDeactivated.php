<?php namespace App\Events;

class UserDeactivated
{

    /**
     * User Model
     */
    public $user;

    function __construct($user)
    {
        $this->user = $user;
    }
}
