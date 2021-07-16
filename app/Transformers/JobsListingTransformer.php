<?php

namespace App\Transformers;

use App\Models\Customer;
use App\Models\Job;
use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOptimized;
use App\Transformers\Optimized\JobProjectsTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;

class JobsListingTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'address',
        'reps',
        'estimators',
        'labours',
        'sub_contractors',
        'customer',
        'job_workflow',
        'follow_up_status',
        'flags',
        'division',
        'financial_details',
        'appointments',
        'job_financial_note',
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['projects', 'production_boards', 'deleted_entity', 'deleted_by', 'flags'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($job)
    {
        $data = [
            'id' => $job->id,
            'name' => $job->name,
            'number' => $job->number,
            'trades' => $job->trades,
            'work_types' => $job->workTypes,
            'customer_id' => $job->customer_id,
            // 'rep_id'                   =>   $job->rep_id,
            'description' => $job->description,
            'same_as_customer_address' => $job->same_as_customer_address,
            'spotio_lead_id' => $job->spotio_lead_id,
            'source_type'    => $job->source_type,
            'amount' => $job->amount,
            'other_trade_type_description' => $job->other_trade_type_description,
            'created_by' => $job->created_by,
            'created_at' => $job->created_at,
            'created_date' => $job->created_date,
            'updated_at' => $job->updated_at,
            'stage_changed_date' => $job->stage_changed_date,
            'distance' => isset($job->distance) ? $job->distance : null,
            'deleted_at' => $job->deleted_at,
            'call_required' => (bool)$job->call_required,
            'appointment_required' => (bool)$job->appointment_required,
            'taxable' => $job->taxable,
            'job_types' => $job->jobTypes,
            'tax_rate' => $job->tax_rate,
            'alt_id' => $job->alt_id,
            'lead_number' => $job->lead_number,
            'duration' => $job->duration,
            'completion_date' => $job->completion_date,
            'to_be_scheduled' => $job->to_be_scheduled,
            'scheduled' => $job->getScheduleStatus(),
            'schedule_count' => $job->schedule_count,
            'current_stage' => $this->getCurrentStage($job),
            'meta' => $this->getJobMeta($job),
            'share_url' => config('app.url') . 'customer_job_preview/' . $job->share_token,
            'moved_to_pb' => $job->moved_to_pb,
            'insurance' => $job->insurance,
            'archived' => $job->archived,
            'archived_cwp' => $job->archived_cwp,
            'contract_signed_date' => $job->cs_date,
            'quickbook_id' => $job->quickbook_id,
            'wp_job' => $job->wp_job,
            'division_code'            =>   $job->division_code,
            'material_delivery_date'   =>   $job->material_delivery_date,
            'purchase_order_number'    =>   $job->purchase_order_number,
            'origin'                   =>   $job->originName(),
            'ghost_job'                =>   $job->ghost_job,
            'quickbook_sync_status'    =>   $job->getQuickbookStatus(),
            'qb_desktop_id'            =>   $job->qb_desktop_id
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
            $data['display_order'] = $job->display_order;
            $data['status'] = $job->projectStatus;
            $data['awarded'] = $job->awarded;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        if ($job->isMultiJob()) {
            $data['projects_count'] = $job->projects_count;
        }

        if ($job->wp_job) {
            $data['wp_job_seen'] = $job->wp_job_seen;
        }

        return $data;
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
            return $this->item($customer, new CustomersTransformerOptimized);
        }
    }

    /**
     * Include Projects
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
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReps($job)
    {
        $reps = $job->reps;
        if ($reps) {
            return $this->transformUser($reps);
        }
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
            return $this->transformUser($estimators);
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
            return $this->transformUser($subContractors);
        }
    }

    /**
     * Include division
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDivision($job)
    {
        $division = $job->division;
        if ($division) {
            return $this->item($division, new DivisionsTransformerOptimized);
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
     * Include JobWorkflow
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobWorkflow($job)
    {
        $jw = $job->jobWorkflow;

        if ($jw) {
            return $this->item($jw, new JobWorkflowTransformer);
        }
    }

    /**
     * Include Phones
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAppointments($job)
    {
        $appointments['today'] = $job->todayAppointments->count();
        $appointments['upcoming'] = $job->upcomingAppointments->count();
        $appointments['today_first'] = $job->todayAppointments->first();
        $appointments['upcoming_first'] = $job->upcomingAppointments->first();

        return $this->item($appointments, function ($appointments) {
            return $appointments;
        });
    }

    /**
     * Include Flags
     *
     * @return customer flag
     **/
    public function includeFlags($job)
    {
        $flags = $job->flags;

        return $this->collection($flags, new FlagsTransformer);
    }

    /**
     * Include FollowUp Status
     *
     * @return League\Fractal\ItemResource
     */
    public function includeFollowUpStatus($job)
    {
        $followUp = $job->currentFollowUpStatusOne;
        if ($followUp) {
            $jobFollowUpTransformer = new JobFollowUpTransformer;
            $jobFollowUpTransformer->setDefaultIncludes(['task']);
            return $this->item($followUp, $jobFollowUpTransformer);
        }
    }

    /**
     * Include Production Board
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProductionBoards($job)
    {
        $productionBoards = $job->productionBoards;
        if ($productionBoards) {
            return $this->collection($productionBoards, new ProductionBoardTransformer);
        }
    }

    /**
     * Include Financial Details
     *
     * @return League\Fractal\ItemResource
     */
    public function includeFinancialDetails($job)
    {
        return $this->item($job, function ($job) {
            return [
                'total_job_amount' => numberFormat($job->total_job_amount),
                'total_change_order_amount' => numberFormat($job->total_change_order_amount),
                'total_amount' => numberFormat($job->total_amount),
                'total_received_payemnt' => numberFormat($job->total_received_payemnt),
                'total_credits' => numberFormat($job->total_credits),
                'pending_payment' => numberFormat($job->pending_payment),
                'total_commission' => numberFormat($job->total_commission),
                'total_account_payable_amount' => numberFormat($job->total_account_payable_amount),
                'sold_out_date' => $job->getSoldOutDate(),
                'can_block_financials' => (int)$job->canBlockFinacials(),
            ];
        });
    }

    public function includeDeletedEntity($job)
    {
        return $this->item($job, function($job){

            return [
                'deleted_at'  => $job->deleted_at,
                'delete_note' => $job->delete_note,
            ];
        });
    }

    public function includeDeletedBy($job)
    {
        $user = $job->deletedBy;
        if($user) {
            return $this->item($user, function($user){

                return [
                    'id'                => (int)$user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'full_name'         => $user->full_name,
                    'full_name_mobile'  => $user->full_name_mobile,
                    'company_name'      => $user->company_name,
                ];
            });
        }

    }

    public function includeJobFinancialNote($job)
    {
        $financialNote = $job->jobFinancialNote;
        if($financialNote){
            return $this->item($financialNote, new JobFinancialNoteTransformer);
        }
    }

    /******************** Private function ********************/

    /**
     * @param object | Job Model instance
     *
     * @return array | current stage data
     */
    private function getCurrentStage($job)
    {
        $ret = ['name' => 'Unknown', 'color' => 'black', 'code' => null, 'resource_id' => null];
        try {
            $currentStage = [];
            $jobWorkflow = $job->jobWorkflow;
            if (is_null($jobWorkflow)) {
                return $ret;
            }
            $stage = $jobWorkflow->stage;
            $currentStage['name'] = $stage->name;
            $currentStage['color'] = $stage->color;
            $currentStage['code'] = $stage->code;
            $currentStage['resource_id'] = $stage->resource_id;
            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }

    private function transformUser($users)
    {
        return $this->collection($users, function ($user) {
            return [
                'id' => (int)$user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->full_name,
                'full_name_mobile' => $user->full_name_mobile,
                'company_name' => $user->company_name,
            ];
        });
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
}
