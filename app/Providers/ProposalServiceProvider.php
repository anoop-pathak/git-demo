<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ProposalServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('proposal', function () {
            return new Demo1;
        });
    }
}
