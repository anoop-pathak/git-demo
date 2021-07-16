<?php

namespace App\Transformers;

use App\Models\Customer;
use App\Models\Job;
use App\Models\Resource;
use App\Models\JobPayment;
use App\Models\WorkCrewNote;
use App\Transformers\Optimized\JobProjectsTransformer;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\DivisionTransformer as DivisionsTransformerOptimized;
use Illuminate\Support\Facades\Auth;
use App\Models\Folder;
use App\Models\Measurement;
use App\Models\Estimation;
use App\Models\Proposal;


class JobsTransformer extends TransformerAbstract
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
        'customer',
        'job_workflow',
        'follow_up_status',
        'resource_ids',
        'labours',
        'sub_contractors',
        'parent'
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'job_workflow_history',
        'notes',
        'most_recent_note',
        'count',
        'appointments',
        'follow_up',
        'flags',
        'workflow',
        'contact',
        'pricing_history',
        'payments',
        'change_order',
        'change_order_history',
        'payment_receive',
        'division',
        'financial_details',
        'projects',
        'selling_price_worksheets',
        'profite_loss_worksheets',
        'custom_tax',
        'work_crew_notes',
        'scheduled_trade_ids',
        'scheduled_work_type_ids',
        'project_address',
        'job_invoices',
        'insurance_details',
        'production_boards',
        'job_invoice_count',
        'job_message_count',
        'job_task_count',
        'upcoming_appointment_count',
        'Job_note_count',
        'custom_fields',
        'hover_job',
        'upcoming_appointment',
        'upcoming_schedule',
        'has_financial',
        'contacts',
        'job_financial_note'
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
            'name' => $job->name,
            'number' => $job->number,
            'trades' => $job->trades,
            'work_types' => $job->workTypes,
            'customer_id' => $job->customer_id,
            // 'rep_id'                   =>   $job->rep_id,
            'description' => $job->description,
            'spotio_lead_id' => $job->spotio_lead_id,
            'source_type'    => $job->source_type,
            'same_as_customer_address' => $job->same_as_customer_address,
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
            'division_code' =>   $job->division_code,
            'alt_id' => $job->alt_id,
            'lead_number' => $job->lead_number,
            'duration' => $job->duration,
            'completion_date' => $job->completion_date,
            'contract_signed_date' => $job->cs_date,
            'to_be_scheduled' => $job->to_be_scheduled,
            'current_stage' => $this->getCurrentStage($job),
            'meta' => $this->getJobMeta($job),
            'has_profit_loss_worksheet' => $this->checkProfitLoseWorksheet($job),
            'has_selling_price_worksheet' => $this->checkSellingPriceWorksheet($job),
            'stage_last_modified' => isset($job->jobWorkflow->stage_last_modified) ? $job->jobWorkflow->stage_last_modified : null,
            'share_token' => $job->share_token,
            'contact_same_as_customer' => (int)$job->contact_same_as_customer,
            'is_old_trade' => $job->isOldTrade(),
            'sold_out_date' => $job->getSoldOutDate(),
            'share_url' => config('app.url') . 'customer_job_preview/' . $job->share_token,
            'moved_to_pb' => $job->moved_to_pb,
            'insurance' => $job->insurance,
            'archived' => $job->archived,
            'archived_cwp' => $job->archived_cwp,
            'scheduled' => $job->getScheduleStatus(),
            'schedule_count' => $job->schedule_count,
            'wp_job' => $job->wp_job,
            'sync_on_hover' =>  $job->sync_on_hover,
            'hover_user_email' =>  $job->hover_user_email,
            'material_delivery_date'   =>  $job->material_delivery_date,
            'purchase_order_number'    =>  $job->purchase_order_number,
            'quickbook_id'             =>  $job->quickbook_id,
            'job_amount_approved_by'   =>  $job->job_amount_approved_by,
            'display_order'            => (int)$job->display_order,
            'origin'                   =>  $job->originName(),
            'ghost_job'                =>  $job->ghost_job,
            'quickbook_sync_status'    =>  $job->getQuickbookStatus(),
            'qb_desktop_id'            =>  $job->qb_desktop_id,
            'new_folder_structure'     =>  (bool)$job->new_folder_structure,
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
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
            return $this->item($customer, new CustomersTransformer);
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

            $transformer = (new JobProjectsTransformer)->setDefaultIncludes([
                'trades',
                'projects',
                'current_stage',
                'work_types',
            ]);

            return $this->collection($projects, $transformer);
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
            return $this->collection($reps, new UsersTransformer);
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
            return $this->collection($estimators, new UsersTransformer);
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
     * Include JobWorkflowHistory
     * @todo Default status include creating problem in larg number of activty_log queries. It also impacting in other modules where this JOb Transformr is used
     * @return League\Fractal\ItemResource
     */
    public function includeJobWorkflowHistory($job)
    {
        $history = $job->jobWorkflowHistory;

        if (sizeof($history) != 0) {
            // Read Todo before uncomment
            // $transformer = (new JobWorkflowHistoryTransformer)->setDefaultIncludes(['status']);
            $transformer = (new JobWorkflowHistoryTransformer);
            return $this->collection($history, $transformer);
        }
    }

    /**
     * Include Notes
     *
     * @return League\Fractal\ItemResource
     */
    public function includeNotes($job, $params)
    {
        list($for_current_stage) = $params['current_stage'];
        if ((bool)$for_current_stage) {
            $current_stage = $job->jobWorkflow->current_stage;
            $notes = $job->notes()
                ->where('stage_code', '=', $current_stage)
                ->get();
        } else {
            $notes = $job->notes;
        }

        if ($notes) {
            return $this->collection($notes, new JobNotesTransformer);
        }
    }

    /**
     * Include MostRecentNote
     *
     * @return League\Fractal\ItemResource
     */
    public function includeMostRecentNote($job)
    {
        $recentNote = $job->notes()
            ->where('job_id', $job->id)
            ->orderBy('id', 'desc')
            ->first();
        if ($recentNote) {
            return $this->item($recentNote, new JobNotesTransformer);
        }
    }

    /**
     * Include count
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCount($job, $params)
    {
        $withEvReports = false;
        if(ine($params,'with_ev_reports')) {
            list($withEvReports) = $params['with_ev_reports'];
        }
        $jobId = $job->id;

        $estimationQueryBuilder = null;
        if($withEvReports == false || $withEvReports == 'false') {
            $estimationQueryBuilder = $job->estimations()->subOnly()->whereNull('ev_report_id');
        }else {
            $estimationQueryBuilder = $job->estimations()->subOnly();
        }

        $proposalsQueryBuilder = $job->proposals()->subOnly();
        $workOrderQueryBuilder = $job->workOrders()->subOnly();
        $materialListQueryBuilder = $job->materialLists()->subOnly();

        $data['estimates']      = $this->getCountWithFolders($estimationQueryBuilder, $jobId, Folder::JOB_ESTIMATION);
        $data['measurements']   = $this->getCountWithFolders($job->measurements(), $jobId, Folder::JOB_MEASUREMENT);
        $data['proposals']      = $this->getCountWithFolders($proposalsQueryBuilder, $jobId, Folder::JOB_PROPOSAL);
        $data['work_orders']    = $this->getCountWithFolders($workOrderQueryBuilder, $jobId, Folder::JOB_WORK_ORDER);;
        $data['material_lists']    = $this->getCountWithFolders($materialListQueryBuilder, $jobId, Folder::JOB_MATERIAL_LIST);;
        $data['work_crew_notes']= $job->workCrewNotes()->onlySubCount()->count();

        $data['job_resources'] = 0;
        $data['stage_resources'] = 0;
        $data['tasks']  =  $job->tasks()->pending()->assignedTo(Auth::id())->count();
        $meta = $this->getJobMeta($job);
        if(ine($meta,'resource_id')) {
            $data['job_resources'] = $this->getResourceCounts($meta['resource_id']);
        }

        $currentStage = $this->getCurrentStage($job);
        if(ine($currentStage,'resource_id')) {
            $data['stage_resources'] = $this->getResourceCounts($currentStage['resource_id']);
        }
        return $this->item($data, function($data){
            return $data;
        });
    }

    /**
     * Include Phones
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAppointments($job)
    {
        $appointments['today'] = $job->appointments()->today()->get()->count();
        $appointments['upcoming'] = $job->appointments()->upcoming()->get()->count();
        $appointments['today_first'] = $job->todayAppointments()->first();
        $appointments['upcoming_first'] = $job->upcomingAppointments()->first();

        return $this->item($appointments, function ($appointments) {
            return $appointments;
        });
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
        $followUp = $job->currentFollowUpStatus()->first();
        if ($followUp) {
            return $this->item($followUp, new JobFollowUpTransformer);
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

    /**
     * Include contact
     *
     * @return League\Fractal\ItemResource
     */
    public function includeContacts($job)
    {
        $jobContacts = $job->contacts;
        if($jobContacts){
            return $this->collection($jobContacts, new JobContactTransformer);
        }
    }

     /**
     * Include contact
     *
     * @return League\Fractal\ItemResource
     */
    public function includeContact($job)
    {
        $contact = $job->primaryContact();

        if($contact) {
            return $this->item($contact, function($contact) {
                $address = $contact->address;
                $emails = $contact->emails->toArray();
                $phones = $contact->phones->toArray();
                $firstEmail = isset($emails[0]) ? $emails[0]['email'] : null;
                $firstPhone = isset($phones[0]) ? $phones[0]['number'] : null;
                unset($emails[0]);
                return [
                    'id'                => $contact->id,
                    'first_name'        => $contact->first_name,
                    'last_name'         => $contact->last_name,
                    'full_name'         => $contact->full_name,
                    'full_name_mobile'  => $contact->full_name_mobile,
                    'email'             => $firstEmail,
                    'phone'             => $firstPhone,
                    'address'           => $address ? $address->address : null,
                    'address_line_1'    => $address ? $address->address_line_1 : null,
                    'zip'               => $address ? zipCodeFormat($address->zip, $address->country_id) : null,
                    'city'              => $address ? $address->city : null,
                    'country_id'        => $address ? $address->country_id : null,
                    'country'           => $address ? $address->country: null,
                    'state'             => $address ? $address->state : null,
                    'additional_emails' => array_values($emails),
                    'additional_phones' => array_values($phones),
                ];
            });
        }
    }

    /**
     * Include Pricing History
     *
     * @return League\Fractal\ItemResource
     */
    public function includePricingHistory($job)
    {
        $pricing = $job->pricingHistory;
        return $this->collection($pricing, new JobPricingHistoryTransformer);
    }

    /**
     * Include Payments
     *
     * @return League\Fractal\ItemResource
     */
    public function includePayments($job)
    {
        $payments = $job->payments;

        return $this->collection($payments, new JobPaymentTransformer);
    }

    /**
     * Include ChangeOrder
     *
     * @return League\Fractal\ItemResource
     */
    public function includeChangeOrder($job)
    {
        $changeOrders = $job->changeOrderHistory();
        if ($changeOrders) {
            return $this->item($changeOrders, function ($changeOrders) {

                return [
                    'count' => $changeOrders->whereNull('canceled')->count(),
                    'total_amount' => $changeOrders->whereNull('canceled')->sum('total_amount'),
                    'entities' => [
                        [
                            'amount' => null,
                            'description' => null,
                        ]
                    ],
                ];
            });
        }
    }

    /**
     * Include ChangeOrderHistory
     *
     * @return League\Fractal\ItemResource
     */
    public function includeChangeOrderHistory($job)
    {
        $changeOrderHistory = $job->changeOrderHistory;
        if ($changeOrderHistory) {
            return $this->collection($changeOrderHistory, new ChangeOrderTransformer);
        }
    }

    /**
     *
     * @param  [type] $job [description]
     * @return [array]      [total amount recieve]
     */
    public function includePaymentReceive($job)
    {
        $totalAmountReceive = $job->payments()
            ->whereNull('canceled')
            ->sum('payment');

        $paymentIds = JobPayment::whereJobId($job->id)->pluck('id')->toArray();
        $totalRefPayment = 0;
        foreach($paymentIds as $id) {
            $totalRefPayment += JobPayment::whereRefId($id)->get()->sum('payment');
        }
        $totalAmount = $totalAmountReceive - $totalRefPayment;

        return $this->item($totalAmount, function ($totalAmount) {
            return [
                'total_amount' => $totalAmount
            ];
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
     * Include financial details
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
                'job_invoice_amount' => numberFormat($job->job_invoice_amount),
                'job_invoice_tax_amount' => numberFormat($job->job_invoice_tax_amount),
                'sold_out_date' => $job->getSoldOutDate(),
                'can_block_financials' => $job->canBlockFinacials(),
                'unapplied_credits' => numberFormat($job->unapplied_credits),
                'applied_payment' => numberFormat($job->total_invoice_received_payment),
                'total_refunds' => numberFormat($job->total_refunds),
                'total_account_payable_amount' => numberFormat($job->total_account_payable_amount),
            ];
        });
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
     * Include WorkCrewNotes
     * @param  Instance $job $job
     * @return WorkCrewNote
     */
    public function includeWorkCrewNotes($job)
    {
        $wcNotes = $job->workCrewNotes;
        if ($wcNotes) {
            return $this->collection($wcNotes, new WorkCrewNotesTransformer);
        }
    }

    /**
     * includeScheduledTradeIds Scheduled Trade Ids
     */
    public function includeScheduledTradeIds($job)
    {
        $trades = $job->scheduleTradeIds->pluck('id')->toArray();
        if ($trades) {
            return $this->primitive(['data' => $trades]);
        }
    }

    /**
     * includeScheduledWorkTypeIds $job
     */
    public function includeScheduledWorkTypeIds($job)
    {
        $workTypes = $job->scheduleWorkTypeIds->pluck('id')->toArray();
        if ($workTypes) {
            return $this->primitive(['data' => $workTypes]);
            // return $this->collection($workTypes, function ($workType) {
            //     return $workType;
            // });
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProjectAddress($job)
    {

        if ($job->isProject()) {
            $address = $job->parentJob->address;

            if ($address) {
                return $this->item($address, new AddressesTransformer);
            }
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
     * Include invoice
     * @param  Instance $job Job
     * @return Resonse
     */
    public function includeJobInvoices($job)
    {
        $invoices = $job->jobInvoices;

        $invoiceTrans = new JobInvoiceTransformer;
        $invoiceTrans->setDefaultIncludes([]);

        return $this->collection($invoices, $invoiceTrans);
    }

    public function includeInsuranceDetails($job)
    {
        $insurance = $job->insuranceDetails;
        if ($insurance) {
            return $this->item($insurance, function ($insurance) {
                return [
                    'id' => $insurance->id,
                    'insurance_company' => $insurance->insurance_company,
                    'insurance_number' => $insurance->insurance_number,
                    'phone' => $insurance->phone,
                    'fax' => $insurance->fax,
                    'email' => $insurance->email,
                    'adjuster_name' => $insurance->adjuster_name,
                    'adjuster_phone' => $insurance->adjuster_phone,
                    'adjuster_email' => $insurance->adjuster_email,
                    'contingency_contract_signed_date'    => $insurance->contingency_contract_signed_date,
                    'date_of_loss'      => $insurance->date_of_loss,
                    'rcv' => $insurance->rcv,
                    'supplement'        => $insurance->supplement,
                    'net_claim'         => $insurance->net_claim,
                    'acv' => $insurance->acv,
                    'depreciation'      => $insurance->depreciation,
                    'deductable_amount' => $insurance->deductable_amount,
                    'policy_number' => $insurance->policy_number,
                    'total' => $insurance->total,
                    'adjuster_phone_ext' => $insurance->adjuster_phone_ext,
                    'upgrade'          =>  $insurance->upgrade
                ];
            });
        }
    }

    /**
     * Include job invoice count
     * @param  Instance $job
     * @return count
     */
    public function includeJobInvoiceCount($job)
    {
        $count = $job->jobInvoices()->count();

        return $this->item($count, function ($count) {

            return [
                'count' => $count
            ];
        });
    }

    /**
     * Include job note count
     * @param  Instance $job Job
     * @return response
     */
    public function includeJobNoteCount($job)
    {
        $count = $job->notes()->count();
         return $this->item($count, function($count){

            return [
                'count' => $count
            ];
        });
    }
     /**
     * Include Upcoming appointment count
     * @param  Instance $job Job
     * @return Response
     */
    public function includeUpcomingAppointmentCount($job)
    {
        $count = $job->upcomingAppointments()->count();
         return $this->item($count, function($count){

            return [
                'count' => $count
            ];
        });
    }
     /**
     * Include job task count
     * @param  Instance $job Job
     * @return Response
     */
    public function includeJobTaskCount($job)
    {
        $count = $job->tasks();
         if(Auth::user()->isSubContractorPrime()) {
            $count = $count->assignedTo(Auth::id());
        }
         $count = $count->count();

        return $this->item($count, function($count){
             return [
                'count' => $count
            ];
        });
     }
     public function includeJobMessageCount($job)
    {
        $count = $job->threadMessages();
         if(Auth::user()->isSubContractorPrime()) {
            $count->participants(Auth::id());
        }
         $count = $count->count();
         return $this->item($count, function($count){
             return [
                'count' => $count
            ];
        });
    }
     /*
     * Include Custom Fields
     * @param  Instance $job Job
     * @return Response
     */
    public function includeCustomFields($job)
    {
        $fields = $job->customFields;
         return $this->collection($fields, function($field) {
            return [
                'name'   => $field->name,
                'value'  => $field->value,
                'type'   => $field->type,
            ];
        });
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
            $hoverTrans = new HoverTransformer;
            $hoverTrans->setDefaultIncludes([]);

            return $this->item($hover, $hoverTrans);
        }
    }

    /**
    * Include Upcoming Appointment
    *
    * @param Instance $job Job
    * @return response
    */
    public function includeUpcomingAppointment($job)
    {
        $appointment = $job->upcomingAppointments()->first();
        if($appointment) {
            $transformer = new AppointmentsTransformer;
            $transformer->setDefaultIncludes([]);
             return $this->item($appointment, $transformer);
        }
    }
     /**
    * Include Upcoming Appointment
    *
    * @param Instance $job Job
    * @return response
    */
    public function includeUpcomingSchedule($job)
    {
        $schedule = $job->upcomingSchedules()->first();
        if($schedule) {
             $transformer = new JobScheduleTransformer;
            $transformer->setDefaultIncludes([]);
             return $this->item($schedule, $transformer);
        }
    }

    public function includeHasFinancial($job)
    {
        return $this->item($job, function ($job) {
            return [
                'can_block_financials' => (bool)$job->canBlockFinacials(),
            ];
        });
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
     * @param integer | Resource id
     *
     * @return int
     */
    private function getResourceCounts($resourceId)
    {
        $root = Resource::find($resourceId);
        if ($root) {
            $resource = $root->descendants();

            //exclude admin only dir/files for standard user.
            if (!Auth::user()->isAuthority()) {
                $resource->excludeAdminOnlyDirectory();
            }

            return $resource->file()->count();
        }
        return 0;
    }

    /**
     * @param object | Job Model instance
     *
     * @return bool
     */
    private function checkProfitLoseWorksheet($job)
    {
        $worksheet = $job->hasProfitLoseWorksheet();

        if ($worksheet) {
            return $worksheet->id;
        }

        return false;
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

            if(\Request::has('incomplete_task_lock_count')) {
                $lockedTasks = $job->tasks()->where('locked', true)
                    ->whereNull('completed')
                    ->where('stage_code', $stage->code)
                    ->whereNull('tasks.deleted_at');
                $currentStage['incomplete_task_lock_count'] = $lockedTasks->count();
            }
            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }

    /**
     * Get count using query builder and also get total number of folders for requested query builder.
     *
     * @param Eloquent $query: query builder
     * @param String $jobNumber: string of job number.
     * @param String $type: string of type.
     */
    private function getCountWithFolders($query, $jobId = null, $type)
	{
        $fCount = Folder::whereJobId($jobId)
            ->whereType($type)
            ->whereCompanyId(getScopeId())
            ->isDir()
            ->count();
        return ($query->count() + $fCount);
	}
}
