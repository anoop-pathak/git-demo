<?php

namespace App\Http\CustomerWebPage\Transformers;

use App\Http\CustomerWebPage\Transformers\JobProjectsTransformer;
use League\Fractal\TransformerAbstract;
use App\Http\CustomerWebPage\Transformers\AddressesTransformer;
use App\Http\CustomerWebPage\Transformers\CustomersTransformer;
use App\Http\CustomerWebPage\Transformers\JobTypesTransformer;
use App\Http\CustomerWebPage\Transformers\JobFinancialCalculationTransformer;


class JobsTransformer extends TransformerAbstract
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
    protected $availableIncludes = [
        'address',
        'customer',
        'work_types',
        'trades',
        'projects',
        'financial_details',
        'hover_job'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($job)
    {
        $data = [
            'id'   => $job->id,
            'name' => $job->name,
            'number' => $job->number,
            'alt_id' => $job->alt_id,
            'lead_number' => $job->lead_number,
            'multi_job'   => $job->multi_job,
            'share_token' => $job->share_token
        ];

        return $data;
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
   //   */
    public function includeCustomer($job)
    {
        $customer = $job->customer;
        if ($customer) {

            return $this->item($customer, new CustomersTransformer);
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAddress($job)
    {
        $address = $job->address;
        if ($address) {

            return $this->item($address, new AddressesTransformer);
        }
    }

    

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($job)
    {
        $trades = $job->trades;
        if($trades) {
			return $this->collection($trades, function($trade) {
                return [
                	'id'   => $trade->id,
                	'name' => $trade->name,
                ];
            }); 
        }
    }

    /**
     * Include workTypes
     *
     * @return League\Fractal\ItemResource
     */

    public function includeWorkTypes($schedule) {
        $worktypes = $schedule->workTypes;
        if($worktypes){

            return $this->collection($worktypes, new JobTypesTransformer);
        }
    }

    public function includeProjects($job)
    {
        if ($job->isMultiJob()) {
            $projects = $job->projects;

            $transformer = (new JobProjectsTransformer)->setDefaultIncludes(['work_types','trades']);

            return $this->collection($projects, $transformer);
        }
    }

     public function includeFinancialDetails($job)
    {
        $financialDetails = $job->financialCalculation;
        if ($financialDetails) {
            return $this->item($financialDetails, new JobFinancialCalculationTransformer);
        }
    }
    
    public function includeHoverJob($job)
    {
        $hoverJob = $job->hoverJob;
        if($hoverJob) {
            return $this->item($hoverJob, new HoverJobTransformer);
        }  
    }
}
