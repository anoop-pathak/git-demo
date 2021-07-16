<?php

namespace App\Transformers;

use App\Transformers\Optimized\CustomersTransformer as CustomersTransformerOtimized;
use App\Transformers\Optimized\JobsTransformer as JobsTransformerOtimized;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOtimized;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;
use Settings;
use App\Transformers\AttachmentsTransformer;

class AppointmentsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['user'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'customer',
        'attendees',
        'jobs',
        'created_by',
        'result_option',
         'reminders',
         'attachments',
         'attachments_count'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($appointment)
    {

        //get occurence
        $occurence = null;
        if ($appointment->isRecurring()) {
            if ($appointment->occurence) {
                $occurence = (int)$appointment->occurence;
            } elseif ($appointment->until_date) {
                $occurence = 'until_date';
            } else {
                $occurence = 'never_end';
            }
        }

        $untilDate = null;

        if ($appointment->until_date) {
            $dateTime = Carbon::parse($appointment->until_date);
            $dateTime->setTimezone(\Settings::get('TIME_ZONE'));
            $untilDate = $dateTime->format('Y-m-d');
        }

        return [
            'id' => $appointment->recurring_id,
            'title' => $appointment->title,
            'description' => $appointment->description,
            'start_date_time' => $appointment->start_date_time,
            'end_date_time' => $appointment->end_date_time,
            'location' => $appointment->location,
            'customer_id' => ((int)$appointment->customer_id) ? $appointment->customer_id : null,
            'job_id' => $appointment->job_id,
            'user_id' => $appointment->user_id,
            'lat' => $appointment->lat,
            'long' => $appointment->long,
            'attendees_ids' => $appointment->attendees->pluck('id')->toArray(),
            'full_day' => $appointment->full_day,
            'location_type' => $appointment->location_type,
            'invites' => $appointment->invites,
            'repeat' => $appointment->repeat,
            'occurence' => $occurence,
            'by_day' => $appointment->by_day,
            'until_date' => $untilDate,
            'series_id' => $appointment->series_id,
            'is_recurring' => $appointment->isRecurring(),
            'interval' => $appointment->interval,
            'is_completed' => (bool)$appointment->completed_at,
            'result' => $appointment->result,
            'result_option_ids' => $appointment->result_option_ids,
        ];
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($appointment)
    {
        $customer = $appointment->customer;
        if ($customer) {
            return $this->item($customer, new CustomersTransformerOtimized);
        }
    }

    /**
     * Include Job
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobs($appointment)
    {
        $jobs = $appointment->jobs;
        if ($jobs) {
            $jobsTrans = new JobsTransformerOtimized;
            $jobsTrans->setDefaultIncludes([
                'work_types',
                'customer',
                'address'
            ]);

            return $this->collection($jobs, $jobsTrans);
        }
    }

    /**
     * Include Attendees
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAttendees($appointment)
    {
        $attendees = $appointment->attendees;
        if (sizeof($attendees)) {
            return $this->collection($attendees, new UsersTransformerOtimized);
        }
    }

    /**
     * Include User
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUser($appointment)
    {
        $user = $appointment->user;
        if ($user) {
            return $this->item($user, new UsersTransformerOtimized);
        }
    }

    /**
     * Include created by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($appointment)
    {
        $user = $appointment->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformerOtimized);
        }
    }

    /**
     * Include created by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeResultOption( $appointment )
    {
        $resultOption = $appointment->resultOption;
        if($resultOption){
            return $this->item($resultOption, function($option) {
                return [
                    'id' => $option->id,
                    'name' => $option->name
                ];
            });
        }
    }

     /**
     * Include Reminders
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReminders( $appointment )
    {
        $reminders = $appointment->reminders;
         return $this->collection($reminders, function($reminder) {
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
    public function includeAttachments($appointment)
    {
        $attachments = $appointment->attachments;
        if($attachments) {
            return $this->collection($attachments, new AttachmentsTransformer);
        }
    }

    /**
     * Include Attachments count
     * @param  Instance $appointment Appointment
     * @return Response
     */
    public function includeAttachmentsCount($appointment)
    {
        $count = $appointment->attachments()->count();

        return $this->item($count, function($count){

            return [
                'count' => $count
            ];
        });
    }
}
