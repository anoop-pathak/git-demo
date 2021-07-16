<?php
namespace App\Services\QuickBooks\Facades;

use Illuminate\Support\Facades\Facade;

class QBOQueue extends Facade
{
    protected static function getFacadeAccessor() { return 'qb-queue'; }
}