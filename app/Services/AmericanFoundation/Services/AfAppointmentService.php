<?php
namespace App\Services\AmericanFoundation\Services;

use App\Services\Grid\CommanderTrait;
use Illuminate\Support\Facades\Auth;

class AfAppointmentService
{
    use CommanderTrait;

    public function __construct()
    {
        //
    }

    public function createJpAppointment($inputs)
    {

        $inputs['created_by'] = Auth::id();
        $customer = $this->execute("App\Commands\AppointmentCommand", ['input' => $inputs]);
        return $customer;
    }

}