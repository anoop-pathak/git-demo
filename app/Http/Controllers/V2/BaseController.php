<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;

class BaseController extends Controller
{

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
        // $this->middleware('company_scope.setHeaders');
    }


    protected function setupLayout()
    {
        if (!is_null($this->layout)) {
            $this->layout = view($this->layout);
        }
    }
}
