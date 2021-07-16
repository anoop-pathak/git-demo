<?php
namespace App\Services\QuickBooks\Facades;

use Illuminate\Support\Facades\Facade;

class QBJob extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'qb-job'; }
}