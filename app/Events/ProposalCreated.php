<?php

namespace App\Events;

class ProposalCreated
{

    /**
     * Proposal Model
     */
    public $proposal;

    public function __construct($proposal)
    {
        $this->proposal = $proposal;
    }
}
