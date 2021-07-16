<?php
namespace App\Services\QuickBooks\Facades;

use Illuminate\Support\Facades\Facade;

class Refund extends Facade
{
    protected static function getFacadeAccessor() { return 'qb-refund'; }
}