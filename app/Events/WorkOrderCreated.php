<?php

namespace App\Events;

class WorkOrderCreated
{

    /**
     * WorkOrder Model
     */
    public $workOrder;

    public function __construct($workOrder)
    {
        $this->workOrder = $workOrder;
    }
}
