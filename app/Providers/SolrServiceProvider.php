<?php

namespace App\Providers;

use App\Services\Solr\Solr;
use Illuminate\Support\ServiceProvider;

class SolrServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind('solr', function () {
            return new Solr;
        });
    }
}
