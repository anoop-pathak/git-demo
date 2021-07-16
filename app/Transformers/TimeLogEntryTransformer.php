<?php

namespace App\Transformers;

use FlySystem;
use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class TimeLogEntryTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['user', 'customer', 'trades', 'job_address', 'division', 'job'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($timeLog)
    {

        return [
            'entry_id' => $timeLog->id,
            'job_id' => $timeLog->job_id,
            'multi_job' => $timeLog->multi_job,
            'number' => $timeLog->number,
            'name'   => $timeLog->name,
            'parent_id' => $timeLog->parent_id,
            'alt_id' => $timeLog->alt_id,
            'user_id' => $timeLog->user_id,
            'user_name'        => $timeLog->user_name,
            'customer_name'    => $timeLog->customer_name,
            'start_date_time' => $timeLog->start_date_time,
            'end_date_time' => $timeLog->end_date_time,
            'duration' => durationFromSeconds($timeLog->duration),
            'lat' => $timeLog->lat,
            'long' => $timeLog->long,
            'location'  => $timeLog->check_in_location,
            'clock_in_note' => $timeLog->clock_in_note,
            'clock_out_note' => $timeLog->clock_out_note,
            'check_in_image' => ($timeLog->check_in_image) ? FlySystem::publicUrl($timeLog->getCheckInImagePath()) : null,
            'check_out_image' => ($timeLog->check_out_image) ? FlySystem::publicUrl($timeLog->getCheckOutImagePath()) : null,
            'check_in_image_thumb' => ($cit = $timeLog->check_in_image_thumb) ? FlySystem::publicUrl($cit) : null,
            'check_out_image_thumb' => ($cot = $timeLog->check_out_image_thumb) ? FlySystem::publicUrl($cot) : null,
            'check_out_location'    =>  $timeLog->check_out_location,
        ];
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($timeLog)
    {
        $customer = $timeLog->customer;
        if ($customer) {
            $customerOptimized = new CustomersTransformerOptimized;
            $customerOptimized->setDefaultIncludes([]);

            return $this->item($customer, $customerOptimized);
        }
    }

    /**
     * Include User
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUser($timeLog)
    {
        $user = $timeLog->user;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobAddress($timelog)
    {
        $job = $timelog->job;
        if($job) {
            $address = $timelog->job->address;
            return $this->item($address, new AddressesTransformer);
        }
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($timelog)
    {
        $job = $timelog->job;
        if($job) {
            $trades = $timelog->job->trades;
            $trans = new TradesTransformer;
            $trans->setDefaultIncludes([]);
            return $this->collection($trades, $trans);
        }
    }

    public function includeJob($timelog){
        $job = $timelog->job;
        if($job) {
            return $this->item($job, function($job) {
                $data = [
                    'id'        => $job->id,
                    'number'    => $job->number,
                    'archived'  => $job->archived,
                    'parent_id' => $job->parent_id,
                    'multi_job' => $job->multi_job,
                    'alt_id'    => $job->alt_id,
                    'name'      => $job->name,
                ];
                return $data;
            });
        }
    }

    public function includeDivision($timeLog)
    {
        $job = $timeLog->job;
        if($job) {
            $division = $job->division;
            if($division){

                return $this->item($division, new DivisionTransformer);
            }
        }
    }
}
