<?php

namespace App\Events;

class NewMessageEvent
{

    /**
     * Message Model
     */
    public $message;

    function __construct($message, $participantIds)
    {
        $this->message = $message;
        $this->participantIds = $participantIds;
    }
}
