<?php

namespace App\Transformers;

use App\Models\Material;
use App\Transformers\Optimized\CustomersTransformer as CustomerTrans;
use App\Transformers\Optimized\JobsTransformer as JobTrans;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;
use App\Transformers\AttachmentsTransformer;
use App\Models\ScheduleRecurring;

class JobScheduleTransformer extends TransformerAbstract
{
    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['job', 'customer'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'created_by',
        'modified_by',
        'labours',
        'sub_contractors',
        'reps',
        'trades',
        'work_types',
        'work_crew_notes',
        'work_orders',
        'material_lists',
        'reminders',
        'attachments',
        'attachments_count'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($schedule)
    {

        return [
            'id' => $schedule->recurring_id,
            'job_id' => $schedule->job_id,
            'title' => $schedule->title,
            'description' => $schedule->description,
            'start_date_time' => $schedule->start_date_time,
            'end_date_time' => $schedule->end_date_time,
            'created_at' => $schedule->created_at,
            'updated_at' => $schedule->updated_at,
            'customer_id' => $schedule->customer_id,
            'subject_edited' => (bool)$schedule->subject_edited,
            'repeat' => $schedule->repeat,
            'occurence' => $schedule->occurence,
            'series_id' => $schedule->series_id,
            'is_recurring' => $schedule->isRecurring(),
            'type' => $schedule->type,
            'full_day' => $schedule->full_day,
            'is_completed' => (bool)$schedule->completed_at,
        ];
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($schedule)
    {
        $user = $schedule->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJob($schedule)
    {
        $jobs = $schedule->job;
        $JobsTransformer = new JobTrans;
        $JobsTransformer->setDefaultIncludes(['address', 'division']);
        if ($jobs) {
            return $this->item($jobs, $JobsTransformer);
        }
    }

    /**
     * Include modified_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeModifiedBy($schedule)
    {
        $user = $schedule->modifiedBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOptimized);
        }
    }

    public function includeCustomer($schedule)
    {
        $customer = $schedule->customer;
        if ($customer) {
            $customerTransformer = new CustomerTrans;
            $customerTransformer->setDefaultIncludes(['rep']);

            return $this->item($customer, $customerTransformer);
        }
    }

    /**
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReps($schedule)
    {
        $recurring = ScheduleRecurring::findOrFail($schedule->recurring_id);
        $reps = $recurring->recurringsReps;
        if ($reps) {
            return $this->collection($reps, new UsersTransformerOptimized);
        }
    }

    /**
     * Include labours
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLabours($schedule)
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
    public function includeSubContractors($schedule)
    {
        $recurring = \ScheduleRecurring::findOrFail($schedule->recurring_id);
        $subContractors = $recurring->recurringsSubContractors;
        if ($subContractors) {
            return $this->collection($subContractors, new UsersTransformerOptimized);
        }
    }

    /**
     * Include trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($schedule)
    {
        $trades = $schedule->trades;
        if ($trades) {
            $trade = new TradesTransformer;
            $trade->setDefaultIncludes([]);

            return $this->collection($trades, $trade);
        }
    }

    /**
     * Include workTypes
     *
     * @return League\Fractal\ItemResource
     */
    public function includeWorkTypes($schedule)
    {
        $worktypes = $schedule->workTypes;
        if ($worktypes) {
            return $this->collection($worktypes, new JobTypesTransformer);
        }
    }

    public function includeWorkCrewNotes($schedule)
    {
        $workCrewNotes = $schedule->workCrewNotes;
        if ($workCrewNotes) {
            return $this->collection($workCrewNotes, new WorkCrewNotesTransformer);
        }
    }

    /**
     * Include Work order
     * @param  Instance $schedule Schedule
     * @return Work order
     */
    public function includeWorkOrders($schedule)
    {
        $workOrders = $schedule->workOrders;
        if ($workOrders) {
            return $this->collection($workOrders, function ($workOrder) {

                return [
                    'id' => $workOrder->id,
                    'title' => $workOrder->title,
                    'thumb' => $workOrder->getThumb(),
                    'is_file' => $workOrder->is_file,
                    'file_name' => str_replace(' ', '_', $workOrder->file_name),
                    'file_path' => $workOrder->getFilePath(),
                    'file_mime_type' => $workOrder->file_mime_type,
                    'file_size' => $workOrder->file_size,
                    'worksheet_id' => $workOrder->worksheet_id,
                    'created_at' => $workOrder->created_at,
                    'updated_at' => $workOrder->updated_at,
                ];
            });
        }
    }

    /**
     * Include Material list
     * @param  Instance $schedule Schedule
     * @return Material List
     */
    public function includeMaterialLists($schedule)
    {
        $materialLists = $schedule->materialLists;
        if ($materialLists) {
            return $this->collection($materialLists, function ($materialList) {

                return [
                    'id' => $materialList->id,
                    'title' => $materialList->title,
                    'thumb' => $materialList->getThumb(),
                    'is_file' => $materialList->is_file,
                    'file_name' => str_replace(' ', '_', $materialList->file_name),
                    'file_path' => $materialList->getFilePath(),
                    'file_mime_type' => $materialList->file_mime_type,
                    'file_size' => $materialList->file_size,
                    'worksheet_id' => $materialList->worksheet_id,
                    'created_at' => $materialList->created_at,
                    'updated_at' => $materialList->updated_at,
                ];
            });
        }
    }

     /**
     * Include Reminders
     * @param  Instance $schedule Schedule
     * @return Work order
     */
    public function includeReminders($schedule)
    {
        $reminders = $schedule->reminders;
         return $this->collection($reminders, function($reminder){
            return [
                'id'        => $reminder->id,
                'type'      => $reminder->type,
                'minutes'   => $reminder->minutes,
            ];
        });
    }

    /**
     * Include Attachments
     * @param  Instance $schedule Schedule
     * @return Attachments
     */
    public function includeAttachments($schedule)
    {
        $attachments = $schedule->attachments;
        if($attachments) {
            return $this->collection($attachments, new AttachmentsTransformer);
        }
    }

    /**
     * Include Attachments count
     * @param  Instance $schedule Schedule
     * @return Response
     */
    public function includeAttachmentsCount($schedule)
    {
        $count = $schedule->attachments()->count();

        return $this->item($count, function($count){

            return [
                'count' => $count
            ];
        });
    }
}
