<?php

namespace App\Events;

class ShareProposalStatus
{
    /**
     * Proposal Model
     */
    public $proposal;
    public $thankYouEmail;

    public function __construct($proposal, $thankYouEmail = true)
    {
        $this->proposal = $proposal;
        $this->thankYouEmail = $thankYouEmail;
    }
}
