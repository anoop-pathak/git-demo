<?php

namespace App\Services\Solr;

use Illuminate\Support\Facades\Facade;

class SolrFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'solr';
    }
}
