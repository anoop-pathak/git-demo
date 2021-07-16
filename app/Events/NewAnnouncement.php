<?php

namespace App\Events;

class NewAnnouncement
{

    /**
     * Annoucement Model
     */
    public $annoucement;

    public $meta;

    public function __construct($announcement, $meta)
    {
        $this->announcement = $announcement;

        $this->meta = $meta;
    }
}
