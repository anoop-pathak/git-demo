<?php
namespace App\Services\AmericanFoundation\Services;

use App\Services\Grid\CommanderTrait;

class AfCustomerService
{
    use CommanderTrait;

    public function __construct()
    {
        //
    }

    public function createJpCustomer($inputs)
    {
        $customer = $this->execute("App\Commands\CustomerCommand", ['input' => $inputs]);

        return $customer;
    }

}