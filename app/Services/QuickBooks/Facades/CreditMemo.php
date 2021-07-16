<?php
namespace App\Services\QuickBooks\Facades;

use Illuminate\Support\Facades\Facade;

class CreditMemo extends Facade
{
    protected static function getFacadeAccessor() { return 'qb-credit-memo'; }
}