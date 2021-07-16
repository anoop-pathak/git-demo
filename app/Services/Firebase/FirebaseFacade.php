<?php

namespace App\Services\Firebase;

use Illuminate\Support\Facades\Facade;

class FirebaseFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'firebase';
    }
}
