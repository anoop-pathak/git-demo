<?php
namespace App\Services\AmericanFoundation\Services;

use App\Services\Grid\CommanderTrait;

class AfUserService
{
    use CommanderTrait;

    public function __construct()
    {
        //
    }

    public function createStandardUser($inputs)
    {
        $user = $this->execute("App\Commands\UserCreateCommand", ['input' => $inputs]);
        return $user;
    }

}