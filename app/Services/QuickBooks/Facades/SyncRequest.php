<?php
namespace App\Services\QuickBooks\Facades;

use Illuminate\Support\Facades\Facade;

class SyncRequest extends Facade {

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'qb-sync-request'; }
}