<?php

namespace App\Commands;

use Settings;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AppointmentCommand implements ShouldQueue
{

     use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * array of all fields submitted
     * @var Array
     */
    public $input;
    public $appointmentData;
    public $startDateTime;
    public $endDateTime;
    public $attendees = [];
    public $edit = false;
    public $jobIds = [];
    public $invites = false;
    public $onlyThis = false;
    public $impactType = false;
    public $attachments = [];
	public $delete_attachments = [];

    /**
     * Consturctor function
     * @var $inputs Array
     * @return Void
     */
    public function __construct($input)
    {
        $this->input = $input;

        if (ine($input, 'job_ids')) {
            $this->jobIds = $input['job_ids'];
        }

        if (ine($input, 'full_day')) {
            $date = new Carbon($input['date'], Settings::get('TIME_ZONE'));
            $this->startDateTime = $date->toDateTimeString();
            $this->endDateTime = $date->addHours(23)->addMinutes(59)
                ->toDateTimeString();
        } else {
            $this->startDateTime = $input['start_date_time'];
            $this->endDateTime = $input['end_date_time'];
        }

        if (isset($input['invites'])) {
            $this->invites = (array)$input['invites'];
        }

        if (ine($input, 'only_this')) {
            $this->onlyThis = true;
        }

        if (ine($input,'attachments')) {
			$this->attachments = $input['attachments'];
        }

		if (ine($input,'delete_attachments')) {
			$this->delete_attachments = $input['delete_attachments'];
		}

        $this->extractInput();
        $this->isEditMode();
    }

    private function extractInput()
    {
        $this->mapAppointmentInput();
        $this->mapAttendees();
    }

    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\AppointmentCommandHandler::class);

        return $commandHandler->handle($this);
    }

    /**
     * Check is it edit mode;
     * @return void
     */
    private function isEditMode()
    {
        if (isset($this->input['id']) && !empty($this->input['id'])) {
            $this->appointmentData['id'] = $this->input['id'];
            $this->appointmentData['previous_user_id'] = $this->input['previous_user_id'];
            $this->appointmentData['previous_attendees'] = $this->input['previous_attendees'];
            $this->edit = true;
            unset($this->appointmentData['created_by']);
        }
    }

    /**
     * Map  Customer Model inputs
     * @return void
     */
    private function mapAppointmentInput()
    {
        $map = [
            'user_id',
            'title',
            'description',
            'location',
            'customer_id',
            'job_id',
            'created_by',
            'full_day',
            'location_type',
            'repeat',
            'occurence',
            'interval',
            'until_date',
            'by_day',
            'exdates',
            'reminders'
        ];

        $this->appointmentData = $this->mapInputs($map);

        if (!in_array($this->appointmentData['repeat'], ['monthly', 'weekly'])) {
            $this->appointmentData['by_day'] = null;
        }

        if (ine($this->input, 'impact_type')) {
            $this->impactType = $this->input['impact_type'];
        }

        switch ($this->impactType) {
            case 'only_this':
                $this->appointmentData['until_date'] = null;
                $this->appointmentData['occurence'] = null;
                $this->appointmentData['by_day'] = null;
                $this->appointmentData['repeat'] = null;
                $this->appointmentData['interval'] = 1;
                break;
        }

        if ($this->appointmentData['occurence'] != 'until_date') {
            $this->appointmentData['until_date'] = null;
        }

        switch ($this->appointmentData['occurence']) {
            case 'never_end':
                $this->impactType = null;
                $this->appointmentData['until_date'] = null;
                $this->appointmentData['occurence'] = null;
                break;

            case 'never_repeat':
                $this->impactType = null;
                $this->appointmentData['repeat'] = null;
                $this->appointmentData['occurence'] = null;
                $this->appointmentData['by_day'] = null;
                $this->appointmentData['until_date'] = null;
                $this->appointmentData['interval'] = 1;
                break;

            case 'until_date':
                $untilDate = Carbon::parse($this->appointmentData['until_date'])->endOfDay();
                $this->appointmentData['occurence'] = null;
                $this->appointmentData['until_date'] = utcConvert($untilDate);
                break;
        }

        if (ine($this->appointmentData, 'repeat')
            && !ine($this->appointmentData, 'interval')) {
            $this->appointmentData['interval'] = 1;
        }

        if (ine($this->appointmentData, 'until_date')) {
            $this->setRecurenceParams();
        }

        if (ine($this->appointmentData, 'location_type') && ($this->appointmentData['location_type'] != 'other')) {
            $this->appointmentData['location'] = null;
        }
    }

    private function mapAttendees()
    {
        if (isset($this->input['attendees']) && !empty($this->input['attendees'])) {
            $this->attendees = $this->input['attendees'];
        }
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function mapInputs($map, $input = [])
    {
        $ret = [];

        // empty the set default.
        if (empty($input)) {
            $input = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? $input[$value] : "";
            } else {
                $ret[$key] = isset($input[$value]) ? $input[$value] : "";
            }
        }

        return $ret;
    }

    private function setRecurenceParams()
    {
        $tz = Settings::get('TIME_ZONE');
        $repeat = $this->appointmentData['repeat'];
        $interval = $this->appointmentData['interval'];
        $untilDate = $this->appointmentData['until_date'];
        $occurence = $this->appointmentData['occurence'];
        $byDay = $this->appointmentData['by_day'];

        $freq = strtoupper($repeat);
        $rules = "FREQ={$freq};";

        if ($interval) {
            $rules .= "INTERVAL={$interval};";
        }

        if ($occurence && (!$untilDate)) {
            $rules .= "COUNT={$occurence};";
        }

        if ($freq && !($untilDate || $occurence)) {
            if ($freq == 'YEARLY') {
                $rules .= "COUNT=" . config('jp.appointment_yearly_occurence_limit') . "};";
            } else {
                $rules .= "COUNT=" . config('jp.appointment_occurence_limit') . ";";
            }
        }

        if ($untilDate) {
            $dateTime = new Carbon($untilDate);
            $dateTime->setTimezone($tz);
            $dateTime->endOfDay();
            $untilDate = $dateTime->format('Y-m-d H:i:s');
            $rules .= "UNTIL={$untilDate};";
        }

        if (!empty($byDay)) {
            $byDay = implode(',', $byDay);
            $rules .= "BYDAY={$byDay}"; //SU,MO,TU,WE,TH,FR,SA,-1MO(for last monday),1MO(1st monday)
        }

        $rule = new \Recurr\Rule(
            $rules,
            Carbon::parse($this->startDateTime, $tz),
            Carbon::parse($this->endDateTime, $tz)
        );
        $transformer = new \Recurr\Transformer\ArrayTransformer();
        $recurrings = $transformer->transform($rule);

        if (!count($recurrings)) {
            $this->appointmentData['until_date'] = null;
            $this->appointmentData['occurence'] = 1;
            $this->appointmentData['by_day'] = null;
            $this->appointmentData['repeat'] = 'daily';
            $this->appointmentData['interval'] = 1;
        }
    }
}
