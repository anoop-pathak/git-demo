<?php

namespace App\Http\OpenAPI\Transformers;

use App\Http\OpenAPI\Transformers\CustomersTransformer;
use App\Http\OpenAPI\Transformers\JobsTransformer;
use App\Http\OpenAPI\Transformers\UsersTransformer;
use Carbon\Carbon;
use League\Fractal\TransformerAbstract;
use Settings;

class AppointmentsTransformer extends TransformerAbstract
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
        'user',
        'customer',
        'attendees',
        'jobs',
        'created_by',
        'result_option',
        'reminders',
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
            'result' => !empty($appointment->result) ? array_values($appointment->result) : [],
            'result_option_id' =>  $appointment->result_option_id,
            'created_at' => $appointment->created_at,
            'updated_at' => $appointment->updated_at
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
            $transformer = new CustomersTransformer;
            $transformer->setDefaultIncludes([]);

            return $this->item($customer, $transformer);
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
            $transformer = new JobsTransformer;
            $transformer->setDefaultIncludes([]);

            return $this->collection($jobs, $transformer);
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
            return $this->collection($attendees, new UsersTransformer);
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
            $transformer = new UsersTransformer;
            $transformer->setDefaultIncludes([]);
            $transformer->setAvailableIncludes([]);

            return $this->item($user, $transformer);
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
            $transformer = new UsersTransformer;
            $transformer->setDefaultIncludes([]);
            $transformer->setAvailableIncludes([]);

            return $this->item($user, $transformer);
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
}
