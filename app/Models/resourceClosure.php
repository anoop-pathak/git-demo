<?php

namespace App\Models;

use Franzose\ClosureTable\Contracts\ClosureTableInterface;

class resourceClosure extends \Franzose\ClosureTable\Models\ClosureTable implements ClosureTableInterface
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'resource_closure';
}
