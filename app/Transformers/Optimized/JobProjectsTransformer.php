<?php

namespace App\Transformers\Optimized;

use App\Models\Job;
use App\Transformers\AddressesTransformer;
use App\Transformers\CustomTaxesTransformer;
use App\Transformers\JobContactTransformer;
use App\Transformers\JobFollowUpTransformer;
use App\Transformers\JobTypesTransformer;
use App\Transformers\LabourTransformer;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOpti;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Transformers\Optimized\ParentJobTransformer;

class JobProjectsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['trades', 'projects', 'current_stage', 'work_types'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['address', 'reps', 'labours', 'sub_contractors', 'work_types', 'customer', 'estimators', 'follow_up', 'follow_up_status', 'schedule', 'contact', 'custom_tax', 'job_types', 'division', 'parent'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($job)
    {
        $data = [
            'id' => $job->id,
            'customer_id' => $job->customer_id,
            'number' => $job->number,
            'name'   => $job->name,
            'alt_id' => $job->alt_id,
            'division_code'    => $job->division_code,
            'duration' => $job->duration,
            'work_crew_notes' => $job->work_crew_notes,
            'to_be_scheduled' => $job->to_be_scheduled,
            'created_at' => $job->created_at,
            'created_date' => $job->created_date,
            'updated_at' => $job->updated_at,
            'description' => $job->description,
            'meta' => $this->getJobMeta($job),
            'scheduled' => $job->getScheduleStatus(),
            'schedule_count' => $job->schedule_count,
            'deleted_at' => $job->deleted_at,
            'archived' => $job->archived,
            'has_selling_price_worksheet' => $this->checkSellingPriceWorksheet($job),
            'display_order'    => (int)$job->display_order,
            'origin'            =>  $job->originName(),
            'ghost_job'         =>  $job->ghost_job,
            'quickbook_sync_status' => $job->getQuickbookStatus(),
            'qb_desktop_id' => $job->qb_desktop_id,
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
            $data['status'] = $job->projectStatus;
            $data['awarded'] = $job->awarded;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        return $data;
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($job)
    {
        return $this->item($job, function ($job) {
            return $job->trades->toArray();
        });
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProjects($job)
    {
        if ($job->isMultiJob()) {
            $projects = $job->projects;
            return $this->collection($projects, new JobProjectsTransformer);
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
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReps($job)
    {
        $reps = $job->reps;
        if ($reps) {
            return $this->collection($reps, new UsersTransformerOpti);
        }
    }

    /**
     * Include labours
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLabours($job)
    {
        $labours = [];

        // suppprt old mobile app to manage labor after labor enhancement
        return $this->collection($labours, function () {
            return [];
        });
    }

    /**
     * Include sub_contractors
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSubContractors($job)
    {
        $subContractors = $job->subContractors;
        if ($subContractors) {
            return $this->collection($subContractors, new LabourTransformer);
        }
    }

    /**
     * Job work types
     * @param type $job
     * @return type
     */
    public function includeWorkTypes($job)
    {
        $workTypes = $job->workTypes;
        if ($workTypes) {
            return $this->collection($workTypes, new JobTypesTransformer);
        }
    }

    /**
     * Job work types
     * @param type $job
     * @return type
     */
    public function includeJobTypes($job)
    {
        $jobTypes = $job->jobTypes;
        if ($jobTypes) {
            return $this->collection($jobTypes, new JobTypesTransformer);
        }
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($job)
    {
        $customer = $job->customer;
        if ($customer) {
            return $this->item($customer, new CustomersTransformer);
        }
    }

    /**
     * Include Current Stage
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCurrentStage($job)
    {
        $currentStage = $job->getJobCurrentStage();
        return $this->item($job, function ($job) use ($currentStage) {
            return $currentStage;
        });
    }

    /**
     * Include estimator
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEstimators($job)
    {
        $estimators = $job->estimators;
        if ($estimators) {
            return $this->collection($estimators, new UsersTransformer);
        }
    }

    /**
     * Include FollowUp
     *
     * @return League\Fractal\ItemResource
     */

    public function includeFollowUp($job)
    {
        $followUps = $job->jobFollowUp;
        return $this->collection($followUps, new JobFollowUpTransformer);
    }

    /**
     * Include FollowUp Status
     *
     * @return League\Fractal\ItemResource
     */

    public function includeFollowUpStatus($job)
    {
        $followUp = $job->currentFollowUpStatus()->first();
        if ($followUp) {
            return $this->item($followUp, new JobFollowUpTransformer);
        }
    }

    /**
     * Include Schedule
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSchedule($job)
    {
        $schedule = $job->schedules;
        if ($schedule) {
            // $jobScheduleTransformer = new JobScheduleTransformer;
            // $jobScheduleTransformer->setDefaultIncludes([]);

            return $this->item($schedule, function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'title' => $schedule->title,
                    'start_date_time' => $schedule->start_date_time,
                    'end_date_time' => $schedule->end_date_time,
                ];
            });
        }

        return null;
    }

    /**
     * Include contact
     *
     * @return League\Fractal\ItemResource
     */
    public function includeContact($job)
    {
        $contact = $job->primaryContact();
        if ($contact) {
            return $this->item($contact, new JobContactTransformer);
        }
    }

    /**
     * @param object | Job class instance
     *
     * @return array
     */
    private function getJobMeta($job)
    {
        $meta = $job->jobMeta;

        if ($meta) {
            $metaData = [];
            foreach ($meta as $key => $value) {
                $metaData[$value['meta_key']] = $value['meta_value'];
            }
        }
        return $metaData;
    }

    /**
     * Include Custom Tax
     * @param  Job Instance $job Job
     * @return Custom Tax
     */
    public function includeCustomTax($job)
    {
        $customTax = $job->customTax;
        if ($customTax) {
            return $this->item($customTax, new CustomTaxesTransformer);
        }
    }

    /**
     * @param object | Job Model instance
     *
     * @return bool
     */
    private function checkSellingPriceWorksheet($job)
    {
        $worksheet = $job->hasSellingPriceWorksheet();

        if ($worksheet) {
            return $worksheet->id;
        }

        return false;
    }

    /**
     * Include division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivision($job) {
        $division = $job->division;
         if($division){
             return $this->item($division, new DivisionsTransformerOptimized);
        }
    }

    /**
     * Include Parent of Job
     * @param Job
     * @return ParentJob
     */
    public function includeParent($job)
    {
        $parent = $job->parentJob;

        if($parent) {
            return $this->item($parent, new ParentJobTransformer);
        }
    }
}
