<?php

namespace App\Events;

class EstimationCreated
{

    /**
     * Estimation Model
     */
    public $estimation;

    public function __construct($estimation)
    {
        $this->estimation = $estimation;
    }
}
