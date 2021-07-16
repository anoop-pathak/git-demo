<?php

namespace App\Events;

use App\Models\GoogleClient;

class GoogleTokenExpired
{
    /**
     * Appointment Model
     */
    public $googleClient;

    function __construct(GoogleClient $googleClient)
    {
        $this->googleClient = $googleClient;
    }
}
