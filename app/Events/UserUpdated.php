<?php namespace App\Events;

class UserUpdated
{

    /**
     * User Model
     */
    public $user;

    /**
     * User Data Array
     */
    public $userData;

    function __construct($user, $userData = [])
    {
        $this->user = $user;
        $this->userData = $userData;
    }
}
