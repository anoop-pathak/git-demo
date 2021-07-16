<?php

namespace App\Presenters;

use App\Models\Customer;
use App\Models\Job;
use Laracasts\Presenter\Presenter;

class EmailPresenter extends Presenter
{


    /**
     * Get Customer name
     * @return Customer name
     */
    public function customerName()
    {
        if (!$this->customer) {
            return false;
        }

        return $this->customer->full_name;
    }

    /**
     * Get Job Number
     * @return Job Number
     */
    public function jobIdReplace()
    {
        if (!$this->jobs->count()) {
            return false;
        }

        if ($this->jobs->count() > 1) {
            $name = 'Multi';
        } else {
            $job = $this->jobs->first();
            $name = $job->present()->jobIdReplace;
        }

        return $name;
    }

    /**
     * Get Job Address
     * @return Job Address
     */
    public function jobAddress()
    {
        $ret = [];
        if((!$this->jobs->count()) || ($this->jobs->count() > 1)) return false;
        $job = $this->jobs->first();
        if(!($address = $job->address)) return false;
        return $address->present()->fullAddress;
    }

}
