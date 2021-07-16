<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * Setup the layout used by the controller.
     *
     * @return void
     */

    protected $pagination_limit = 10;

    public function __construct()
    {
        $this->middleware('company_scope.apply');
        $this->middleware('company_scope.validateCustomerJob');
    }

    public function executeCommand($command, $data) {
        $command = new $command($data);

        return $command->handle();
    }
}
