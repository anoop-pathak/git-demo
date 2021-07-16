<?php

namespace App\Transformers\Optimized;

use App\Models\Job;
use App\Transformers\AddressesTransformer;
use App\Transformers\CustomTaxesTransformer;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use App\Transformers\JobFollowUpTransformer;
use App\Transformers\JobInvoiceTransformer;
use App\Transformers\JobTypesTransformer;
use App\Transformers\JobWorkflowTransformer;
use App\Transformers\WorkflowTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\FlagsTransformer;
use App\Transformers\QBSyncJobTransformer;


class JobsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['customer', 'estimators', 'address', 'current_stage', 'parent', 'job_workflow', 'workflow'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'reps',
        'flags',
        'labour_ids',
        'sub_ids',
        'financial_details',
        'trades',
        'invoices',
        'resource_ids',
        'work_types',
        'projects',
        'follow_up_status',
        'customer',
        'custom_tax',
        'division',
        'description',
        'hover_job',
        'flags',
        'address',
        'parent',
        'financial_calculations',
        'financial_count',
        'mapped_qb_job',
        'qb_job'
    ];

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
            'name'   =>   $job->name,
            'division_code' =>   $job->division_code,
            'spotio_lead_id' => $job->spotio_lead_id,
            'source_type'    => $job->source_type,
            'alt_id' => $job->alt_id,
            'lead_number' => $job->lead_number,
            'amount' => $job->amount,
            'taxable' => $job->taxable,
            'tax_rate' => $job->tax_rate,
            'invoice_id' => $job->invoice_id,
            'meta' => $this->getJobMeta($job),
            'current_stage' => $job->getJobCurrentStage(),
            'duration' => $job->duration,
            'completion_date' => $job->completion_date,
            'contract_signed_date' => $job->cs_date,
            'archived' => $job->archived,
            'material_delivery_date'   =>   $job->material_delivery_date,
            'post_office_number'       =>   $job->post_office_number,
            'quickbook_id'             =>   $job->quickbook_id,
            'job_amount_approved_by'   =>   $job->job_amount_approved_by,
            'created_at'               =>   $job->created_at,
            'origin'                   =>   $job->originName(),
            'ghost_job'                =>   $job->ghost_job,
            'quickbook_sync_status'    =>   $job->getQuickbookStatus(),
            'qb_desktop_id'            =>   $job->qb_desktop_id,
            'updated_at'               =>   $job->updated_at,
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
            $data['display_order'] = $job->display_order;
            $data['status'] = $job->projectStatus;
            $data['awarded'] = $job->awarded;
        } else {
            $data['multi_job'] = $job->multi_job;
        }

        if ($job->isMultiJob() && (!$job->hideProjectCount)) {
            $data['projects_count'] = $job->projects_count;
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
            return $this->item($customer, new CustomersTransformer);
        }
    }

    /**
     * Include Parent for projects
     *
     * @return League\Fractal\ItemResource
     */
    public function includeParent($job)
    {
        if ($job->isProject() && ($parentJob = $job->parentJob)) {
            return $this->item($parentJob, new JobProjectsTransformer);
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
     * Include Financial Details
     *
     * @return League\Fractal\ItemResource
     */
    public function includeFinancialDetails($job)
    {
        return $this->item($job, function ($job) {
            return [
                'can_block_financials' => $job->canBlockFinacials(),
            ];
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
     * Include Invoices
     *
     * @return League\Fractal\ItemResource
     */
    public function includeInvoices($job)
    {
        $invoices = $job->invoices;
        if ($invoices) {
            $transformer = new JobInvoiceTransformer;
            $transformer->setDefaultIncludes([]); // no default
            return $this->collection($invoices, $transformer);
        }
    }

    /* Include job rep ids
     *
     * @return League\Fractal\ItemResource
     */
    public function includeRepIds($job)
    {
        $ids = $job->reps->pluck('id')->toArray();

        return $this->item($ids, function ($ids) {
            return $ids;
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
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReps($job)
    {
        $reps = $job->reps;
        if ($reps) {
            return $this->collection($reps, new UsersTransformer);
        }
    }

    /**
     * Include labour ids
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLabourIds($job)
    {
        $ids = [];

        return $this->item($ids, function ($ids) {
            return $ids;
        });
    }

    /**
     * Include labour ids
     *
     * @return League\Fractal\ItemResource
     */
    public function includesubIds($job)
    {
        $ids = $job->subContractors->pluck('id')->toArray();

        return $this->item($ids, function ($ids) {
            return $ids;
        });
    }

    /**
     * include for resource ids
     * @param  [instance] $job [descriptio]
     * @return [array]      [resource ids]
     */
    public function includeResourceIds($job)
    {
        $ids = [];

        if (isset($job->workflow->resource_id)) {
            $ids['workflow_resource_id'] = $job->workflow->resource_id;
        }

        if (isset($job->company->subscriberResource->value)) {
            $ids['company_resource_id'] = $job->company->subscriberResource->value;
        }

        return $this->item($ids, function ($ids) {

            return [
                'workflow_resource_id' => array_key_exists('workflow_resource_id', $ids) ? $ids['workflow_resource_id'] : null,
                'company_resource_id' => array_key_exists('company_resource_id', $ids) ? $ids['company_resource_id'] : null
            ];
        });
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
     * Include Job Description
     *
     * @return League\Fractal\ItemResource
     */
    public function includeDescription($job)
    {

        $decription = $job->description;
        if ($decription) {
            return $this->item($decription, function ($decription) {
                return [
                    'job_description' => $decription,
                ];
            });
        }
    }

    /**
    * include hover job
    *
    * @param Instance $job Job
    * @return response
    */
    public function includeHoverJob($job) {
        $hover = $job->hoverJob;
        if($hover) {
            return $this->item($hover, function($hover){
                return [
                    'hover_job_id' => $hover->hover_job_id,
                ];
            });
        }
    }

    /**
     * Include financial calculations
     *
     * @return League\Fractal\ItemResource
     */
    public function includeFinancialCalculations($job)
    {
        return $this->item($job, function($job){
            return [
                'total_job_amount'          => numberFormat($job->total_job_amount),
                'total_change_order_amount' => numberFormat($job->total_change_order_amount),
                'total_amount'              => numberFormat($job->total_amount),
                'total_received_payemnt'    => numberFormat($job->total_received_payemnt),
                'total_credits'             => numberFormat($job->total_credits),
                'total_refunds'             => numberFormat($job->total_refunds),
                'pending_payment'           => numberFormat($job->pending_payment),
                'total_commission'          => numberFormat($job->total_commission),
                'job_invoice_amount'        => numberFormat($job->job_invoice_amount),
                'job_invoice_tax_amount'    => numberFormat($job->job_invoice_tax_amount),
                'sold_out_date'             => $job->getSoldOutDate(),
                'can_block_financials'      => $job->canBlockFinacials(),
                'unapplied_credits'         => numberFormat($job->unapplied_credits),
                'applied_payment'           => numberFormat($job->total_invoice_received_payment),
                'total_invoice_amount'      => numberFormat($job->job_invoice_amount + $job->job_invoice_tax_amount),
                'total_change_order_invoice_amount' => numberFormat($job->total_change_order_invoice_amount),
                'total_account_payable_amount' => numberFormat($job->total_account_payable_amount),
            ];
        });
    }

    public function includeFinancialCount($job)
    {
        return $this->item($job, function($job){
            return [
                'total_payments' => (int)$job->total_payments,
                'total_change_orders_with_invoice' => (int)$job->total_change_orders_with_invoice,
                'total_job_invoices' => (int)$job->total_job_invoices,
                'total_credits' => (int)$job->total_credit_count,
                'total_bills' => (int)$job->total_bill_count,
                'total_refund_count' => (int)$job->total_refund_count,
            ];
        });
    }

    public function includeMappedQbJob($job)
    {
        $mappedJob = $job->qbMappedJob;
        if($mappedJob){
            $qbJob =  $mappedJob->qbJob;

            if($qbJob) {
                return $this->item($qbJob, new QBSyncJobTransformer);
            }
        }
    }

    public function includeqbJob($job)
    {
        $qbJob = $job->qbJob;

        if($qbJob){
            return $this->item($qbJob, new QBSyncJobTransformer);
        }
    }

    /******************** Private function ********************/

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
     * Include JobWorkflow
     * @todo Default status include creating problem in larg number of activty_log queries. It also impacting in other modules where this JOb Transformr is used
     * @return League\Fractal\ItemResource
     */
    public function includeJobWorkflow($job)
    {
        $jw = $job->jobWorkflow;

        if ($jw) {
            // Read Todo before uncomment
            // $transformer = (new JobWorkflowTransformer)->setDefaultIncludes(['status']);
            $transformer = (new JobWorkflowTransformer);
            return $this->item($jw, $transformer);
        }
    }

    /**
     * Include Workflow
     *
     * @return League\Fractal\ItemResource
     */

    public function includeWorkflow($job)
    {
        $workflow = $job->workflow;
        if ($workflow) {
            return $this->item($workflow, new WorkflowTransformer);
        }
    }
}
