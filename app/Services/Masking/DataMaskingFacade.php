<?php

namespace App\Services\Masking;
use Illuminate\Support\Facades\Facade;

class DataMaskingFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
    	return 'dataMasking'; 
    }
}