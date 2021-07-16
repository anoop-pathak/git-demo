<?php

namespace App\Events;

class MaterialListCreated
{

    /**
     * Material List Model
     */
    public $materialList;

    public function __construct($materialList)
    {
        $this->materialList = $materialList;
    }
}
