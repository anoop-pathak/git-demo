<?php

namespace App\Transformers;

use App\Models\Company;
use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOptimized;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use Illuminate\Support\Facades\App;
use League\Fractal\TransformerAbstract;

class ActivityLogsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'customer',
        'job',
        'created_by',
        'jobs'
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($activityLog)
    {
        return [
            'id' => (int)$activityLog->id,
            'event' => $activityLog->event,
            'customer_id' => $activityLog->customer_id,
            'job_id' => $activityLog->job_id,
            'stage_code' => $activityLog->stage_code,
            'display_data' => $activityLog->display_data,
            'created_at' => $activityLog->created_at,
            'meta_data' => $this->getMetaData($activityLog),
        ];
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($activityLog)
    {
        $customer = $activityLog->customer;
        if ($customer) {
            $transform = new CustomersTransformerOptimized;
            $transform->setDefaultIncludes([]);

            return $this->item($customer, $transform);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($activityLog)
    {
        $job = $activityLog->job;
        if ($job) {
            return $this->item($job, function($job) {
                $data = [
                    'archived'  =>   $job->archived,
                    'id'     =>   $job->id,
                    'number' =>   $job->number,
                    'name'   =>   $job->name,
                    'alt_id' =>   $job->alt_id,
                ];
                if($job->isProject()) {
                    $data['parent_id'] = $job->parent_id;
                }else {
                    $data['multi_job'] = $job->multi_job;
                }
                return $data;
            });
        }
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($activityLog)
    {
        $user = $activityLog->user;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include meta
     *
     * @return League\Fractal\ItemResource
     */
    public function getMetaData($activityLog)
    {
        $data = [];
        $transform = App::make('Sorskod\Larasponse\Larasponse');
        $meta = $activityLog->meta->pluck('value', 'key')->toArray();
        if (ine($meta, 'company')) {
            $company = Company::find($meta['company']);
            if ($company) {
                $data['company'] = $transform->item($company, new SubscriberTransformer);
            }
        }
        return $data;
    }

    /**
     * Include Jobs
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobs($activityLog){
        $jobs = $activityLog->jobs;
        if($jobs) {

            return $this->collection($jobs, function($job) {
                $data = [
                    'id'     =>   $job->id,
                    'name'   =>   $job->name,
                    'number' =>   $job->number,
                    'alt_id' =>   $job->alt_id,
                    'archived'  =>   $job->archived,
                ];

                if($job->isProject()) {
                    $data['parent_id'] = $job->parent_id;
                } else {
                    $data['multi_job'] = $job->multi_job;
                }

                return $data;
            });
        }
    }
}
