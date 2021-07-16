<?php

namespace App\Services\Appointments;

use App\Events\AppointmentCreated;
use App\Events\AppointmentDeleted;
use App\Events\AppointmentJobNoteUpdated;
use App\Events\AppointmentUpdated;
use App\Events\DeleteGoogleAppointment;
use App\Events\OldRecurringAppointmentUpdated;
use App\Models\Appointment;
use App\Models\AppointmentRecurring;
use App\Repositories\AppointmentRepository;
use Firebase;
use App\Models\AppointmentReminder;
use App\Services\Google\GoogleCalenderServices;
use App\Services\Recurr\RecurrService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Settings;
use FlySystem;
use Illuminate\Support\Facades\App;
use App\Models\Resource;
use PDF;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\AppointmentsExportTransformer;
use Excel;
use App\Models\User;
use App\Models\Trade;
use App\Models\Division;
use App\Models\FinancialCategory;
use App\Models\Flag;
use App\Models\Address;

class AppointmentService
{

    public function __construct(AppointmentRepository $repo, RecurrService $recurr, GoogleCalenderServices $calenderServices)
    {
        $this->repo = $repo;
        $this->recurrService = $recurr;
        $this->calenderServices = $calenderServices;
    }

    /**
     * Save Appointment
     * @param  Array $appointmentData Appointment Data
     * @param  DateTime $startDateTime Start Date Time
     * @param  DateTime $endDateTime End Date Time
     * @param  array $attendees Attendees Ids
     * @param  array $jobIds Job Id
     * @param  array $invites Emails
     * @return Appointment
     */
    public function save($appointmentData, $startDateTime, $endDateTime, $attendees = [], $jobIds = [], $invites = [], $attachments = array())
    {
        if (!$appointmentData['interval']) {
            $appointmentData['interval'] = 1;
        }

        DB::beginTransaction();
        try {
            $appointment = $this->repo->createAppointment($appointmentData, $attendees, $jobIds, $invites, $attachments);
            $appointment = $this->saveRecurringAppointment($appointment, $startDateTime, $endDateTime);

            $this->saveReminders($appointment, $appointmentData['reminders']);
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        $this->fireCreateEvent($appointment);

        return $appointment;
    }


    /**
     * Update Appointment
     * @param  Id $appointmentId Appointment Id
     * @param  Array $appointmentData Appointment Data
     * @param  DateTime $startDateTime Start Date Time
     * @param  DateTime $endDateTime End Date Time
     * @param  array $attendees Attendees Ids
     * @param  array $jobIds Job Ids
     * @param  boolean $invites $Invites
     * @param  boolean $onlyThis Only This
     * @return $appointment
     */
    public function update($appointmentId, $appointmentData, $startDateTime, $endDateTime, $attendees = [], $jobIds = [], $invites = false, $onlyThis = false, $impactType = false, $attachments = array(), $deletedAttachments = array())
    {
        DB::beginTransaction();
        try {
            $updateParentAppointment = $onlyThis;
            $appointment = $this->repo->getById($appointmentId);
            $appointmentData['previous_user_id'] = $appointment->user_id;
            $this->updateFirebaseOldUser($appointment);
            $appointmentId = $appointment->id;
            $seriesId = $appointment->series_id;

            $updateAllRecurring = false;

            if ($appointment->isRecurring()) {
                if ($updateParentAppointment || ($impactType == 'only_this')) {
                    $updateParentAppointment = true;
                    $appointment->deleteRecurring();
                    $appointmentData['series_id'] = $appointment->series_id;
                    $appointment = $this->repo->createAppointment($appointmentData, $attendees, $jobIds, $invites);
                    $appointment = $this->saveRecurringAppointment($appointment, $startDateTime, $endDateTime);
                } elseif ($impactType == 'this_and_following_event') {
                    $updateParentAppointment = true;
                    $startDate = Carbon::parse($appointment->start_date_time)->toDateString();
                    $untilDate = Carbon::parse($appointment->start_date_time)->subDay()->endOfDay();
                    $firstAppointment = $this->repo->getFirstRecurringAppointment($appointmentId, $withTrashed = true);
                    $firstAppointment->occurence = null;
                    $firstAppointment->until_date = $untilDate;
                    $firstAppointment->save();

                    $googleEventId = $firstAppointment->google_event_id;

                    $appointmentStartDate = Carbon::parse($appointment->start_date_time)
                        ->format('Y-m-d H:i');
                    $appointmentEndDate = Carbon::parse($appointment->end_date_time)
                        ->format('Y-m-d H:i');

                    $actualStartDate = utcConvert($startDateTime)->format('Y-m-d H:i');
                    $actualEndDate = utcConvert($endDateTime)->format('Y-m-d H:i');

                    if ((ine($appointmentData, 'occurence'))
                        && ($appointmentStartDate == $actualStartDate)
                        && ($appointmentEndDate == $actualEndDate)
                        && ($appointment->occurence == $appointmentData['occurence'])
                        && ($appointment->repeat == $appointmentData['repeat'])
                        && ($appointment->interval == $appointmentData['interval'])
                        && ($appointment->by_day == $appointmentData['by_day'])
                    ) {
                        $count = AppointmentRecurring::where('appointment_id', $firstAppointment->id)
                            ->where('start_date_time', '>=', $firstAppointment->until_date)
                            ->withTrashed()
                            ->count();
                        $appointmentData['occurence'] = $count;
                    }
                    $dateTime = Carbon::parse($firstAppointment->start_date_time);
                    $dateTime->setTimezone(Settings::get('TIME_ZONE'));
                    $firstStartDate = $dateTime->format('Y-m-d H:i:s');
                    $dateTime = Carbon::parse($firstAppointment->end_date_time);
                    $dateTime->setTimezone(Settings::get('TIME_ZONE'));
                    $firstEndDate = $dateTime->format('Y-m-d H:i:s');

                    $appointmentData['series_id'] = generateUniqueToken();
                    $appointmentData['parent_id'] = $firstAppointment->id;

                    $appointment = $this->repo->createAppointment($appointmentData, $attendees, $jobIds, $invites);

                    $recurringIds = AppointmentRecurring::where('appointment_id', $firstAppointment->id)
                        ->withTrashed()
                        ->where('start_date_time', '>', $firstAppointment->until_date)
                        ->pluck('id')->toArray();

                    if (!empty($recurringIds)) {
                        AppointmentRecurring::withTrashed()
                            ->whereIn('id', $recurringIds)
                            ->update(['appointment_id' => $appointment->id]);
                    }

                    $this->updateRecurringAppointment($firstAppointment, $firstStartDate, $firstEndDate);
                    $appointment = $this->saveRecurringAppointment($appointment, $startDateTime, $endDateTime);

                } else {
                    $updateAllRecurring = true;
                    $appointment = $this->repo->updateAppointment($appointment, $appointmentData, $attendees, $jobIds, $invites);
                    $appointment = $this->updateRecurringAppointment($appointment, $startDateTime, $endDateTime);
                }
            } else {
                $appointment = $this->repo->updateAppointment($appointment, $appointmentData, $attendees, $jobIds, $invites);
                $appointment = $this->saveRecurringAppointment($appointment, $startDateTime, $endDateTime);
            }

            $this->saveReminders($appointment, $appointmentData['reminders']);

            if(!empty($attachments)){
				$type = Appointment::APPOINTMENT;
				$attachments = $appointment->moveAttachments($attachments);
				$appointment->updateAttachments($appointment, $type, $attachments);
			}

			if(!empty($deletedAttachments)) {
				$type = Appointment::APPOINTMENT;
				$appointment->deleteAttachments($appointment, $type, $deletedAttachments);
			}

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }


        if (!$appointment) {
            $this->fireOldRecuringEvent($appointmentId, $appointmentData);
            return false;
        }

        $this->updateFirebase($appointment);

        //events manage
        if ($updateParentAppointment) {
            $this->fireOldRecuringEvent($appointmentId, $appointmentData);
            $this->fireUpdateEvent($appointment, $appointmentData);
        } elseif ($updateAllRecurring) {
            $this->fireOldRecuringEvent($appointmentId, $appointmentData);
            $this->fireJobNoteUpdateEvent($appointment);
        } else {
            $this->fireUpdateEvent($appointment, $appointmentData);
        }

        return $appointment;
    }

    /**
     * Update Appointment By Google Calendar
     * @param  Instance $appointment Appointment Instance
     * @param  Array $appointmentData Array of appointment data
     * @param  DateTime $startDateTime StartDateTime
     * @param  DateTime $endDateTime EndDateTime
     * @return Boolean
     */
    public function updateByGoogleCalendar($appointment, $appointmentData, $startDateTime, $endDateTime)
    {
        $appointmentRecurring = AppointmentRecurring::whereAppointmentId($appointment->id)->first();
        $appointmentRecurring->start_date_time = $startDateTime;
        $appointmentRecurring->end_date_time = $endDateTime;
        $appointmentRecurring->update();
        $attendees = $appointment->attendees->pluck('id')->toArray();
        $jobIds = $appointment->jobs->pluck('id')->toArray();
        $this->repo->updateAppointment($appointment, $appointmentData, $attendees, $jobIds, $invites = false);
    }

    /**
     * Get Appointment by Id
     * @param  Int $id Appointment Id
     * @return Appointment
     *
     */
    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    /**
     * Delete Appointment
     * @param  Instance $appointment Appointment
     * @param  boolean $onlyThis Only This
     * @return Appointment
     */
    public function delete($appointment, $impactType = false)
    {
        DB::beginTransaction();
        try {
            if ($appointment->isRecurring()) {
                if ($impactType == 'this_and_following_event') {
                    $previousAppointment = $this->repo->getLastAppointment($appointment->series_id, $appointment->start_date_time);
                    if ($previousAppointment) {
                        $untilDate = Carbon::parse($appointment->start_date_time)->subDay()->endOfDay();
                        $previousAppointment->occurence = null;
                        $previousAppointment->until_date = $untilDate;
                        $previousAppointment->save();
                    }
                    AppointmentRecurring::leftJoin(
                        'appointments',
                        'appointment_recurrings.appointment_id',
                        '=',
                        'appointments.id'
                    )
                        ->where('series_id', $appointment->series_id)
                        ->where('appointment_recurrings.id', '>=', $appointment->recurring_id)
                        ->forceDelete();
                    $appointment->deleteRecurring();
                } elseif ($impactType == 'only_this') {
                    $appointment->deleteRecurring();
                } else {
                    $appointment->reminders()->delete();
                    $appointment->deleteAllAttachments($appointment, Appointment::APPOINTMENT);
                    $appointment->delete();
                }

                $appointmentData['previous_user_id'] = $appointment->user_id;
            } else {
                $appointment->reminders()->delete();
                $appointment->deleteAllAttachments($appointment, Appointment::APPOINTMENT);
                $appointment->delete();
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        $appointmentCount = Appointment::recurring()->where('appointments.id', $appointment->id)
            ->count();
        if (!$appointmentCount) {
            $this->fireDeleteEvent($appointment);
            $this->removeFromGoogleCalender($appointment);

            return true;
        }

        $this->fireOldRecuringEvent($appointment->id, $appointmentData);
        $this->fireDeleteEvent($appointment);

        return true;
    }

    /**
     * Get Nearest Date
     * @param  Array $filters Filters
     * @return Date
     */
    public function getNearestDate($filters)
    {
        if (!ine($filters, 'job_id') && !ine($filters, 'customer_id')) {
            return null;
        }

        $date = $this->repo->getNearestDate($filters);
        return $date;
    }

    /**
     * Move Appointment
     * @param  Instance $descAppointment Appointment
     * @param  DateTime $startDateTime Start date time
     * @param  DateTime $endDateTime End date time
     * @param  Date $date Date
     * @return Appointment Object
     */
    public function move($descAppointment, $startDateTime, $endDateTime, $fullDay = false, $input = [])
    {
        if ($descAppointment->isRecurring()) {
            $appointmentId = $descAppointment->id;
            $googleEventId = $descAppointment->google_event_id;
            //delete recurring appointment
            AppointmentRecurring::whereId($descAppointment->recurring_id)
                ->first()
                ->delete();

            //appointment data
            $appointmentData = [
                'user_id' => ine($input, 'user_id') ? $input['user_id'] : $descAppointment->user_id,
                'title' => $descAppointment->title,
                'description' => $descAppointment->description,
                'location' => $descAppointment->location,
                'customer_id' => $descAppointment->customer_id,
                'job_id' => $descAppointment->job_id,
                'created_by' => $descAppointment->created_by,
                'full_day' => $fullDay,
                'location_type' => $descAppointment->location_type,
                'repeat' => null,
                'occurence' => 0,
                'series_id' => $descAppointment->series_id,
            ];

            $attendees = $descAppointment->attendees()->pluck('user_id')->toArray();

            // new attendees
            if (isset($input['attendees'])) {
                $attendees = arry_fu((array)$input['attendees']);
            }

            $jobIds = $descAppointment->jobs()->pluck('job_id')->toArray();
            $invites = $descAppointment->invites;

            //create appointment
            $appointment = $this->repo->createAppointment($appointmentData, $attendees, $jobIds, $invites);
            $appointment = $this->saveRecurringAppointment($appointment, $startDateTime, $endDateTime);

            $this->fireOldRecuringEvent($appointmentId);
            // $this->fireUpdateEvent($appointment);
        } else {
            $appointment = $this->saveRecurringAppointment($descAppointment, $startDateTime, $endDateTime);

            $data = [
                'full_day' => $fullDay
            ];

            if (ine($input, 'user_id')) {
                $data['user_id'] = $input['user_id'];
            }

            $appointment->update($data);

            // new attendees
            if (isset($input['attendees'])) {
                $attendees = arry_fu((array)$input['attendees']);
                $appointment->attendees()->detach();
                if (!empty($attendees)) {
                    $appointment->attendees()->attach($attendees);
                }
            }
        }

        //update note and create google appointment
        $appointmentData['previous_user_id'] = ine($input, 'user_id') ? $descAppointment->user_id : null;
        $appointmentData['previous_attendees'] = [];

        $this->fireUpdateEvent($appointment, $appointmentData);

        return $appointment;
    }

    /**
     * add appointment result
     * @param  Instance $appointment Appointment
     * @param  array $meta
     * @return $appointment
     */
    public function addResult($appointment, $meta = [])
    {

        DB::beginTransaction();
        try {   
            $resultOptionIds = ine($meta, 'result_option_ids') ? $meta['result_option_ids'] : [];
            $descAppointment = clone $appointment;
            $appointmentId = $descAppointment->id;
            $googleEventId = $descAppointment->google_event_id;

            if ($appointment->isRecurring()) {
                $fullDay = $appointment->full_day;
                $dateTime = Carbon::parse($appointment->start_date_time);
                $dateTime->setTimezone(\Settings::get('TIME_ZONE'));
                $startDateTime = $dateTime->format('Y-m-d H:i:s');
                $dateTime = Carbon::parse($appointment->end_date_time);
                $dateTime->setTimezone(\Settings::get('TIME_ZONE'));
                $endDateTime = $dateTime->format('Y-m-d H:i:s');
                $appointment = $this->appoinmentRemoveFromRecurring($appointment, $startDateTime, $endDateTime, $fullDay, $meta, $resultOptionIds);
            } else {
                $appointment->result                    = $meta['result'];
                $appointment->result_option_id          = $meta['result_option_id'];
                if(!$appointment->result_option_ids) {
                    $appointment->result_option_ids = $resultOptionIds;
                }
                $appointment->save();
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }

        if ($descAppointment->isRecurring()) {
            $this->fireOldRecuringEvent($appointmentId);
            $this->fireUpdateEvent($appointment);
        }

        return $appointment;
    }

    /**
	 * Save as attachment
	 * @param  [type] $contents [pdf contents]
	 * @return [type] $name [file name]
	 */
	public function saveAsAttachment($contents, $name)
	{
		$pdfObject = $this->makePdf($contents);
		$rootDir 	   = $this->getRootDir();
		$rootPath	   = config('resources.BASE_PATH').$rootDir->path;
		$physicalName  = Carbon::now()->timestamp.'_'.$name;
		$filePath	   = $rootPath.'/'.$physicalName;
		$mimeType 	   = 'application/pdf';
		// save pdf
		FlySystem::put($filePath, $pdfObject->output(), ['ContentType' => $mimeType]);

		$size 		   = FlySystem::getSize($filePath);
		$resourcesRepo = App::make('App\Repositories\ResourcesRepository');
		$resource = $resourcesRepo->createFile($name, $rootDir, $mimeType, $size, $physicalName);

		return $resource;
	}

    public function getCsvFilters($inputs)
	{
		$filters = [];
		$dateRangeType = ine($inputs, 'date_range_type') ? $inputs['date_range_type']: null;
		$startDate = ine($inputs, 'start_date') ? $inputs['start_date']: null;
		$endDate = ine($inputs, 'end_date') ? $inputs['end_date']: null;

		if ($dateRangeType) {
			$filters[] = 'Date Range Type: '.ucwords(str_replace("_", " ", $dateRangeType));;
		}

		if ($startDate || $endDate){
			$filters[] = 'Duration: '.$startDate.' - '.$endDate;
		}

		if(ine($inputs, 'users')){
			$userNames = $this->getUserNames($inputs['users']);

			if(!empty($userNames)){
				$userNames = implode(', ', $userNames);
				$filters[] = 'Assigned To: '.$userNames;
			}
		}

		if(ine($inputs, 'division_ids')){
			$division = Division::where('company_id', getScopeId())
				->whereIn('id', (array)$inputs['division_ids'])
				->pluck('name')
                ->toArray();

			if(!empty($division)){
				$division = implode(', ', $division);
				$filters[] = 'Division(s): '.$division;
			}
		}

		if(ine($inputs, 'category_ids')){
			$category = FinancialCategory::where('company_id', getScopeId())
				->whereIn('id', (array)$inputs['category_ids'])
				->pluck('name')
                ->toArray();

			if(!empty($category)){
				$category = implode(', ', $category);
				$filters[] = 'Category: '.$category;
			}
		}

		if(ine($inputs, 'job_rep_ids')){
			$jobReps = $this->getUserNames($inputs['job_rep_ids']);

			if(!empty($jobReps)){
				$jobReps = implode(', ', $jobReps);
				$filters[] = 'Company Crew: '.$jobReps;
			}
		}

		if(ine($inputs, 'sub_ids')){
			$subs = $this->getUserNames($inputs['sub_ids']);

			if(!empty($subs)){
				$subs = implode(', ', $subs);
				$filters[] = 'Labor/Sub: '.$subs;
			}
		}

		if(ine($inputs, 'trades')){
			$trades = Trade::whereIn('id', (array)$inputs['trades'])->pluck('name')->toArray();;

			if(!empty($trades)){
				$trades = implode(', ', $trades);
				$filters[] = 'Trade Type: '.$trades;
			}
		}

		if(ine($inputs, 'job_flag_ids')){
			$jobFlag = Flag::whereIn('id', (array)$inputs['job_flag_ids'])
                ->pluck('title')
                ->toArray();

			if(!empty($jobFlag)){
				$jobFlag = implode(', ', $jobFlag);
				$filters[] = 'Job Flag(s): '.$jobFlag;
			}
		}

		if(ine($inputs, 'cities')){
			$cities = Address::where('company_id', getScopeId())
				->whereIn('city', (array)$inputs['cities'])
				->pluck('city')
                ->toArray();

			if(!empty($cities)){
				$cities = implode(', ', $cities);
				$filters[] = 'City: '.$cities;
			}
		}

		$allFilters = implode('| ', $filters);

		return array("Filters: ".$allFilters);
	}

	public function exportCsv($filters)
	{
		$response = app(Larasponse::class);

		$appointments = $this->repo->getFilteredAppointments($filters)
			->with(['jobs', 'customer'])
			->get();
		$appointments = $response->collection($appointments, new  AppointmentsExportTransformer);

		if(empty($appointments['data'])) {
			$appointments['data'] = $this->getDefaultColumns();
		}

		$csvFilters = $this->getCsvFilters($filters);

		Excel::create('Appointments', function($excel) use($appointments, $csvFilters){
			$excel->sheet('sheet1', function($sheet) use($appointments, $csvFilters){
				$sheet->mergeCells('A1:N1');
				$sheet->cells('A1:N1', function($cells) {
					$cells->setAlignment('center');
				});
				$sheet->getStyle('A1:N1')->getAlignment()->setWrapText(true);
				$sheet->setHeight(1, 20);
				$sheet->row(1, $csvFilters);
			 	$sheet->prependRow(2, $this->getDefaultColumns());
			 	$sheet->setHeight(2, 15);
				$sheet->rows($appointments['data']);
			});
		})
		->export('xlsx');
	}

	private function getDefaultColumns()
	{
		return [
			'Title',
			'Customer Name',
			'Customer Company Name',
			'Job Id',
			'Appointment For',
			'Attendees',
			'Start Date Time',
			'End Date Time',
			'All Day',
			'location',
			'Note',
			'Additional Recipients',
			'Appointment Result Option',
			'Appointment Results',
		];
	}


    /***********Private Methods *************/

    private function appoinmentRemoveFromRecurring($appointment, $startDateTime, $endDateTime, $fullDay,  $meta = array(), $resultOptionIds = [])
    {

        if (!$appointment->isRecurring()) {
            return false;
        }

        //delete recurring appointment
        AppointmentRecurring::whereId($appointment->recurring_id)
            ->first()
            ->delete();

        //appointment data
        $appointmentData = [
            'user_id' => $appointment->user_id,
            'title' => $appointment->title,
            'description' => $appointment->description,
            'location' => $appointment->location,
            'customer_id' => $appointment->customer_id,
            'job_id' => $appointment->job_id,
            'created_by' => $appointment->created_by,
            'full_day' => $fullDay,
            'location_type' => $appointment->location_type,
            'repeat' => null,
            'occurence' => null,
            'series_id' => $appointment->series_id,
        ];

        $attendees = $appointment->attendees()->pluck('user_id')->toArray();
        $jobIds = $appointment->jobs()->pluck('job_id')->toArray();
        $invites = $appointment->invites;

        //create appointment
        $appointment = $this->repo->createAppointment($appointmentData, $attendees , $jobIds, $invites);
        if(ine($meta, 'result_option_id') && ine($meta, 'result')) {
            $appointment->result                    = $meta['result'];
            $appointment->result_option_id          = $meta['result_option_id'];
            if(!$appointment->result_option_ids) {
                $appointment->result_option_ids = $resultOptionIds;
            }
            $appointment->save();
        }
        $appointment = $this->saveRecurringAppointment($appointment, $startDateTime, $endDateTime);

        return $appointment;
    }

    private function updateFirebaseTodayAppointments($userids = [])
    {
        if (empty($userids)) {
            return false;
        }

        foreach ($userids as $userId) {
            Firebase::updateTodayAppointment($userId);
        }
    }

    private function removeFromGoogleCalender($appointment)
    {
        Event::fire('JobProgress.Appointments.Events.DeleteGoogleAppointment', new DeleteGoogleAppointment($appointment));
    }

    private function saveRecurringAppointment($appointment, $startDateTime, $endDateTime, $result = [])
    {
        if($appointment->isRecurring()) {
            $recurringDates = $this->recurrService->getAppointmentRecurringDates($appointment, $startDateTime, $endDateTime);
            AppointmentRecurring::insert($recurringDates);
            $appointment = $this->repo->getFirstRecurringAppointment($appointment->id);
        }else {
            $recurring = AppointmentRecurring::firstOrNew(['appointment_id' => $appointment->id]);
            $recurring->start_date_time = $startDateTime;
            $recurring->end_date_time   = $endDateTime;
            $recurring->save();
            $subScope = true;
            if(\Auth::check() && \Auth::user()->isSubContractorPrime()) {
                $subScope = false;
            }
            $appointment = $this->repo->getById($recurring->id, $with = [], $subScope);
        }
        return $appointment;
    }


    public function createGoogleAppointment($appointmentData, $startDateTime, $endDateTime, $attendees = [], $jobIds = [], $invites = [])
    {
        $appointmentData['created_from'] = 'google';
        $appointment = $this->repo->createAppointment($appointmentData, $attendees, $jobIds, $invites);

        if ($appointment->isRecurring()) {
            $appointment = $this->updateRecurringAppointment($appointment, $startDateTime, $endDateTime);
        } else {
            $recurring = AppointmentRecurring::firstOrNew(['appointment_id' => $appointment->id]);
            $recurring->start_date_time = $startDateTime;
            $recurring->end_date_time = $endDateTime;
            $recurring->save();
        }

        return $appointment;
    }

    /**
     * Update Appointment By Google Calendar
     * @param  Instance $appointment Appointment Instance
     * @param  Array $appointmentData Array of appointment data
     * @param  DateTime $startDateTime StartDateTime
     * @param  DateTime $endDateTime EndDateTime
     * @return Boolean
     */
    public function updateGoogleAppointment($appointment, $appointmentData, $startDateTime, $endDateTime, $attendees = [], $invites = [])
    {

        $jobIds = $appointment->jobs->pluck('id')->toArray();
        $appointment = $this->repo->updateAppointment($appointment, $appointmentData, $attendees, $jobIds, $invites);

        if ($appointment->isRecurring()) {
            $this->updateRecurringAppointment($appointment, $startDateTime, $endDateTime);
        } else {
            $this->saveRecurringAppointment($appointment, $startDateTime, $endDateTime);
        }

        return $appointment;
    }

    /**
    * mark as completed appointment
    * @param $id (appointment id)
    * @param array of $inputs
    * @return true on success 
    */
    public function markAsCompleted($id, $inputs)
    {
        $completedAt = ine($inputs, 'is_completed') ? Carbon::now() : null;
        $impactType  = ine($inputs, 'impact_type') ? $inputs['impact_type'] : null;
        $appointment = $this->repo->getById($id);
        if($appointment->isRecurring() 
            && in_array($impactType, ['this_and_following_event', 'only_this'])) {
            $appointmentData = [
                'user_id' => $appointment->user_id,
                'title'   => $appointment->title,
                'description' => $appointment->description,
                'location'    => $appointment->location,
                'customer_id' => $appointment->customer_id,
                'created_by'  => $appointment->created_by,
                'full_day'    => $appointment->full_day,
                'location_type'=> $appointment->location_type,
                'repeat'       => $appointment->repeat,
                'occurence'    => $appointment->occurence,
                'interval'     => $appointment->interval,
                'until_date'   => $appointment->until_date,
                'by_day'       => $appointment->by_day,
                'exdates'      => "",
                'completed_at' => $completedAt,
                'series_id'    => $appointment->series_id, 
            ];
            $attendees = $appointment->attendees->pluck('id')->toArray();
            $jobIds    = $appointment->jobs->pluck('id')->toArray();
            $invites   = $appointment->invites;
            $appointment = $this->updateMarkAsCompleted($appointment, 
                    $impactType, 
                    $appointmentData, 
                    $attendees, 
                    $jobIds, 
                    $invites
                );
        } else {
            $appointment->completed_at = $completedAt;
            $appointment->save();
        }
        return $appointment;
    }
    /**
    * update mark as completed
    *
    * @param $appointmentId
    * @param $impactType (all_this, this_and_following, only_this)
    * @return $appointment
    **/
    private function updateMarkAsCompleted($appointment, $impactType, $appointmentData, $attendees, $jobIds, $invites)
    {
        DB::beginTransaction();
        try {
            $newAppointmentId = false;
            $appointment = $this->repo->getById($appointment->recurring_id);
            $oldAppointmentId = $appointment->id;
            if($impactType == 'only_this') {
                $appointment->deleteRecurring();
                $appointmentData['repeat'] = null;
                $appointmentData['occurence'] = null;
                $appointmentData['interval'] = 1;
                $appointmentData['until_date'] = null;
                $appointmentData['by_day'] = [];
                $newAppointment = $this->repo->createAppointment($appointmentData, $attendees , $jobIds, $invites);
                DB::table('appointment_recurrings')
                    ->insert([
                        'appointment_id'  => $newAppointment->id,
                        'start_date_time' => $appointment->start_date_time,
                        'end_date_time'   => $appointment->end_date_time,
                    ]);
                $appointment = $this->repo->getFirstRecurringAppointment($newAppointment->id);
            } elseif($impactType == 'this_and_following_event'){
                $untilDate = Carbon::parse($appointment->start_date_time)->subDay()->endOfDay();
                $firstAppointment  = $this->repo->getFirstRecurringAppointment($appointment->id, $withTrashed = true);
                $firstAppointment->occurence = null;
                $firstAppointment->until_date = $untilDate;
                $firstAppointment->save();
                
                $recurringIds = AppointmentRecurring::where('appointment_id', $firstAppointment->id)
                            ->where('start_date_time', '>=', $firstAppointment->until_date)
                            ->withTrashed()
                            ->pluck('id')->toArray();
                $appointmentData['until_date'] =  null;
                $appointmentData['occurence']  = count($recurringIds);
                $appointmentData['series_id']  = generateUniqueToken();
                $newAppointment = $this->repo->createAppointment($appointmentData, $attendees , $jobIds, $invites);
                AppointmentRecurring::withTrashed()
                    ->whereIn('id', $recurringIds)
                    ->update(['appointment_id' => $newAppointment->id]);
                $appointment = $this->repo->getFirstRecurringAppointment($newAppointment->id);
            }
            DB::commit();
        } catch(Exception $e) {
            DB::rollback();
            throw $e;
        }
        
        $this->fireOldRecuringEvent($oldAppointmentId);
        $this->fireUpdateEvent($appointment);
        return $appointment;
    }


    private function updateRecurringAppointment($appointment, $startDateTime, $endDateTime)
    {
        $appointmentId = $appointment->id;

        if ($appointment->isRecurring()) {
            $recurringDates = $this->recurrService->updateAllRecurringAppointment($appointment, $startDateTime, $endDateTime);
            if (empty($recurringDates)) {
                return null;    
            }

            AppointmentRecurring::insert($recurringDates);
            
            $subScope = true;
            if(\Auth::check() && \Auth::user()->isSubContractorPrime()) {
                $subScope = false;
            }
            
            $appointment = $this->repo->find($appointment->recurring_id, $with = [], $subScope);
            if (!$appointment) {
                $appointment = $this->repo->getLastRecurringAppointment($appointmentId);
            }
        } else {
            AppointmentRecurring::whereAppointmentId($appointment->id)
                ->where('id', '!=', $appointment->recurring_id)
                ->forceDelete();

            $recurring = AppointmentRecurring::find($appointment->recurring_id);
            $recurring->start_date_time = $startDateTime;
            $recurring->end_date_time = $endDateTime;
            $recurring->save();

            $subScope = true;
            if(\Auth::check() && \Auth::user()->isSubContractorPrime()) {
                $subScope = false;
            }
            $appointment = $this->repo->getById($recurring->id, $with = [], $subScope);
        }

        return $appointment;
    }

    private function updateFirebase($appointment)
    {
        $userIds = $appointment->attendees->pluck('id')->toArray();

        //check appointment user exist
        if (($appointment->user)) {
            $userIds[] = $appointment->user_id;
        }

        //firebae user today and upcoming appointment key update
        foreach (arry_fu($userIds) as $userId) {
            Firebase::updateUserUpcomingAppointments($userId);
            Firebase::updateTodayAppointment($userId);
        }

        return true;
    }

    private function fireCreateEvent($appointment)
    {
        Event::fire('JobProgress.Appointments.Events.AppointmentCreated', new AppointmentCreated($appointment));
    }

    public function fireDeleteEvent($appointment)
    {
        Event::fire('JobProgress.Appointments.Events.AppointmentDeleted', new AppointmentDeleted($appointment));
    }

    private function fireJobNoteUpdateEvent($appointment)
    {
        Event::fire('JobProgress.Appointments.Events.AppointmentJobNoteUpdated', new AppointmentJobNoteUpdated($appointment));
    }

    private function fireUpdateEvent($appointment, $appointmentData = [])
    {
        if ($appointment->isRecurring()) {
            $appointment = $this->repo->getFirstRecurringAppointment($appointment->id, $withTrashed = true);
        }

        Event::fire('JobProgress.Appointments.Events.AppointmentUpdated', new AppointmentUpdated($appointment, $appointmentData));
    }

    private function fireOldRecuringEvent($appointmentId, $data = [])
    {
        if(!$appointmentId) {
            return false;
        }

        $appointment = $this->repo->getFirstRecurringAppointment($appointmentId, $withTrashed = true);
        if (!$appointment) {
            $appointment = Appointment::where('id', $appointmentId)->withTrashed()->first();

            if ($appointment) {
                Event::fire('JobProgress.Appointments.Events.DeleteGoogleAppointment', new DeleteGoogleAppointment($appointment));
            }

            return false;
        }

        //for getting previous user id
        if (!ine($data, 'previous_user_id')) {
            $data['previous_user_id'] = $appointment->user_id;
        }

        Event::fire('JobProgress.Appointments.Events.OldRecurringAppointmentUpdated', new OldRecurringAppointmentUpdated($appointment, $data));
    }

    private function updateFirebaseOldUser($appointment)
    {
        $oldAttendees = [];

        if ($todayAppointment = $appointment->isToday()) {
            $oldAttendees = $appointment->attendees->pluck('id')->toArray();
        }

        if (($appointment->user)) {
            $oldAttendees[] = $appointment->user_id;
        }

        if ($todayAppointment) {
            $this->updateFirebaseTodayAppointments(arry_fu($oldAttendees));
        }

        return true;
    }

    /**
     * save appointment reminders
     * @param  $appointment
     * @param  $reminderData
     * @return $appointment
     */
    private function saveReminders($appointment, $reminderData)
    {
        if(!is_array($reminderData)) return $appointment;
        $appointment->reminders()->delete();
        if(empty($reminderData)) return $appointment;
        $data = [];
        $reminderData = array_unique($reminderData, SORT_REGULAR);
        foreach ($reminderData as $key => $value) {
            if(!ine($value, 'type') || !ine($value, 'minutes')) continue;
            $data[] = new AppointmentReminder ([
                'appointment_id' => $appointment->id,
                'type'           => $value['type'],
                'minutes'        => $value['minutes'],
            ]);
        }
        if(!empty($data)) {
            $appointment->reminders()->saveMany($data);
        }
        return $appointment;
    }

    /**
	 * make pdf
	 * @param  [array] $contents
	 * @return pdf instanmce
	 */
	private function makePdf($contents)
	{
		return PDF::loadHTML($contents)
			->setPaper('a4')
			->setOption('no-background', false)
			->setOption('dpi', 200)
			->setOption('viewport-size', 1366);
	}

	private function getRootDir() {
		$parentDir = Resource::name(Resource::EMAIL_ATTACHMENTS)
			->company(getScopeId())
			->first();

		if(!$parentDir){
			$resourceService = App::make('App\Services\Resources\ResourceServices');
			$root = Resource::companyRoot(getScopeId());
			$parentDir = $resourceService->createDir(Resource::EMAIL_ATTACHMENTS, $root->id);
		}
		return $parentDir;
	}

    private function getUserNames($userIds){
		$userNames = User::where('company_id', getScopeId())
			->whereIn('id', (array)$userIds)
			->selectRaw("CONCAT(first_name, ' ', last_name) AS full_name")
			->pluck('users.full_name')
            ->toArray();

		return $userNames;
	}
}