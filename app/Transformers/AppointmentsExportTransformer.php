<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use Carbon\Carbon;
use Settings;

class AppointmentsExportTransformer extends TransformerAbstract {

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
    protected $availableIncludes = [];

     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($appointment) {

        $startDateTime = null;

        $startDateTime = Carbon::parse($appointment->start_date_time);
        $startDateTime->setTimezone(Settings::get('TIME_ZONE'));

        $endDateTime = Carbon::parse($appointment->end_date_time);
        $endDateTime->setTimezone(Settings::get('TIME_ZONE'));

        if($appointment->full_day) {
            $startDateTime = $startDateTime->toDateString();
            $endDateTime = $endDateTime->toDateString();
        }else {
            $startDateTime = $startDateTime->toDateTimeString();
            $endDateTime = $endDateTime->toDateTimeString();
        }

        $customerName = $customerCompanyName = '';
        if($customer = $appointment->customer){
            $customerName = $customer->full_name;
            $customerCompanyName = $customer->company_name;

            if($customer->is_commercial) {
                $secondaryContact = $customer->secondaryNameContact;
                $customerName = ($secondaryContact) ? $secondaryContact->full_name : null;
                $customerCompanyName = $customer->first_name;
            }
        }

        $jobNumbers = $appointment->jobs->pluck('number')->toArray();

        $resultOptionName = '';
        if($resultOption = $appointment->resultOption) {
            $resultOptionName = $resultOption->name;
        }

        $appointmentResult = [];
        if($results = $appointment->result) {
            foreach ($results as $result) {
                $appointmentResult[] =  $result['name'].': '.$result['value'];
            }
        }

        return [
            'Title'                     => $appointment->title,
            'Customer Name'             => $customerName,
            'Customer Company Name'     => $customerCompanyName,
            'Job Id'                    => implode(', ', $jobNumbers),
            'Appointment For'           => $appointment->present()->assignedUserName,
            'Attendees'                 => implode(' , ', $appointment->attendees->pluck('full_name')->toArray()),
            'Start Date Time'           => $startDateTime,
            'End Date Time'             => $endDateTime,
            'All Day'                   => (int)$appointment->full_day,
            'Location'                  => $appointment->location,
            'Note'                      => $appointment->description,
            'Additional Recipients'     => implode(",",$appointment->invites),
            'Appointment Result Option' => $resultOptionName,
            'Appointment Results'       => implode("\n" ,$appointmentResult)
        ];
    }
}