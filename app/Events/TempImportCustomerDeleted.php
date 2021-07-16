<?php

namespace App\Events;

class TempImportCustomerDeleted
{

    /**
     *
     * @param [type] $type [type of customers]
     */

    public function __construct($type)
    {
        $this->type = $type;
    }
}
