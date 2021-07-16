<?php namespace App\Events;

class UserActivated
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
