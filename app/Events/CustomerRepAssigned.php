<?php

namespace App\Events;

class CustomerRepAssigned
{
    /**
     * Customer Model
     */
    public $customer;
    public $newRep;
    public $oldRep;
    public $assignedBy;

    public function __construct($customer, $assignedBy, $newRep, $oldRep = null)
    {
        $this->customer = $customer;
        $this->assignedBy = $assignedBy;
        $this->newRep = $newRep;
        $this->oldRep = $oldRep;
    }
}
