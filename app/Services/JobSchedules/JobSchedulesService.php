<?php

namespace App\Services\JobSchedules;

use PDF;
use FlySystem;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Trade;
use App\Models\Company;
use App\Models\Resource;
use App\Models\JobSchedule;
use App\Events\JobScheduled;
use App\Models\ScheduleReminder;
use App\Models\ScheduleRecurring;
use App\Events\JobScheduleUpdated;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Event;
use Queue;
use App\Services\Recurr\RecurrService;
use App\Repositories\WorkflowRepository;
use App\Repositories\ResourcesRepository;
use App\Repositories\JobSchedulesRepository;
use App\Services\Resources\ResourceServices;
use App\Services\Google\GoogleCalenderServices;
use App\Models\Job;
use Settings;
use App\Models\Setting;

class JobSchedulesService
{
    /**
     * [$repo description]
     * @var [type]
     */
    protected $repo;
    protected $scope;
    protected $calendarService;

    public function __construct(
        JobSchedulesRepository $repo,
        Context $scope,
        JobRepository $jobRepo,
        RecurrService $recurrService,
        GoogleCalenderServices $calendarService
    ) {

        $this->repo = $repo;
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;
        $this->recurrService = $recurrService;
        $this->calendarService = $calendarService;
    }

    /**
     * make new schedule
     * @param  [int] $jobId              [job id]
     * @param  [string] $title           [job schedule title]
     * @param  [dateTime] $startDateTime [job schedule start date time]
     * @param  [dateTime] $endDateTime   [description]
     * @param  [int] $createdBy          [description]
     * @param  [string] $description     [description]
     * @return [JobSchedule]             [jobScheduleObject]
     */
    public function makeSchedule($title, $startDateTime, $endDateTime, $createdBy, $meta = [])
    {

        if (ine($meta, 'repeat') && !ine($meta, 'occurence')) {
            $meta['occurence'] = config('jp.schedule_occurence_limit');
        }
        $schedule = $this->repo->saveSchedule($title, $createdBy, $meta);
        $schedule = $this->saveRecurringSchedule($schedule, $startDateTime, $endDateTime);
        $scheduleReps = $this->repo->saveRepsAndSubcontractor($schedule, $meta);

        // add job schedule to google calendar
        Queue::push('App\Services\JobSchedules\JobSchedulesQueueHandler@syncToGoogleCalendar', [
            'schedule_id' => $schedule->id,
            'current_user_id' => Crypt::encrypt(\Auth::id()),
        ]);

        if ($schedule->type == JobSchedule::EVENT_TYPE) {
            return $schedule;
        }

        if(ine($meta, 'reminders')) {
            $this->saveReminders($schedule, $meta['reminders']);
        }

        Event::fire('JobProgress.Jobs.Events.JobScheduled', new JobScheduled($schedule));

        return $schedule;
    }

    /**
     * Update Schedule
     * @param  JobSchedule $schedule [description]
     * @param  [string]    $title         [description]
     * @param  [dateTime]  $startDateTime [description]
     * @param  [dateTime]  $end_date_time   [description]
     * @param  [int]       $modifiedBy    [description]
     * @param  [string]    $description   [description]
     * @return [schedule]                     [description]
     */
    public function updateSchedule($schedule, $title, $startDateTime, $endDateTime, $modifiedBy, $meta = [])
    {
        $data['schedule_id'] = $schedule->id;
        $data['current_user_id'] = Crypt::encrypt(\Auth::id());

        if (ine($meta, 'repeat') && !ine($meta, 'occurence')) {
            $meta['occurence'] = config('jp.schdule_recurring_limit');
        }

        $recurring = ScheduleRecurring::findOrFail($schedule->recurring_id);
		$oldSubcontractors = $recurring->recurringsSubContractors->pluck('id')->toArray();
		$oldReps = $recurring->recurringsReps->pluck('id')->toArray();

        // get old reps
        if ($schedule->reps) {
            $oldGoogleEventReps = $schedule->reps()->whereNotNull('google_event_id')
                ->pluck('rep_id', 'google_event_id')->toArray();

            $data['old_google_event_reps'] = $oldGoogleEventReps;
        }

        if ($schedule->isRecurring()) {
            if (ine($meta, 'only_this')) {
                $data['old_schedule_id'] = $schedule->id;
                $schedule->deleteRecurring();
                $jobId = $schedule->job_id;
                $meta['repeat'] = null;
                $meta['occurence'] = null;
                $meta['series_id'] = $schedule->series_id;
                $meta['job_id'] = $schedule->job_id;
                $meta['type'] = $schedule->type;
                $schedule = $this->repo->saveSchedule($title, $schedule->created_by, $meta);
                $schedule = $this->saveRecurringSchedule($schedule, $startDateTime, $endDateTime);
            } else {
                $oldOccurence = $schedule->occurence;
                $schedule = $this->repo->updateSchedule($schedule, $title, $modifiedBy, $meta);
                $schedule = $this->updateRecurringSchedule($schedule, $startDateTime, $endDateTime, $oldOccurence);
            }
        } else {
            $schedule = $this->repo->updateSchedule($schedule, $title, $modifiedBy, $meta);
            $schedule = $this->saveRecurringSchedule($schedule, $startDateTime, $endDateTime);
        }

        if ($schedule) {
            $data['schedule_id'] = $schedule->id;
        }

        if ($schedule) {
			$scheduleReps = $this->repo->saveRepsAndSubcontractor($schedule, $meta, $data);
		}

        // update schedule on google calendar
        Queue::push('App\Services\JobSchedules\JobSchedulesQueueHandler@updateGoogleCalendar', $data);

        if (!$schedule) {
            return $schedule;
        }

        if ($schedule->type == JobSchedule::EVENT_TYPE) {
            return $schedule;
        }

        if(ine($meta, 'reminders')) {
            $this->saveReminders($schedule, $meta['reminders']);
        }

        Event::fire('JobProgress.Jobs.Events.JobScheduleUpdated', new JobScheduleUpdated($schedule, $oldSubcontractors, $oldReps));

        return $schedule;
    }

    /**
     * delete schedule
     * @param  [object] $schedule
     * @param  [array] $input
     * @return [object] $schedule
     */
    public function deleteSchedule($schedule, $input)
    {
        $data = [
            'schedule_id' => $schedule->id,
            'current_user_id' => Crypt::encrypt(\Auth::id()),
        ];

        if ($schedule->reps) {
            $googleEventReps = $schedule->reps()->whereNotNull('google_event_id')
                ->pluck('rep_id', 'google_event_id')->toArray();
            $data['google_event_reps'] = $googleEventReps;
        }

        if (ine($input, 'only_this')) {
            $schedule->deleteRecurring();
            $data['only_this'] = true;
        } else {
            // $schedule->reps()->detach();
            $schedule->deleteAllAttachments($schedule, JobSchedule::SCHEDULE_TYPE);
            $schedule->deleteAll();
        }

        // remove job schedule from google calendar
        Queue::push('App\Services\JobSchedules\JobSchedulesQueueHandler@removeFromGoogleCalendar', $data);

        return $schedule;
    }

    /**
     * Get job schedule by id
     * @param $id Integer | Id Associate to job schedule
     * @return Object
     */
    public function getById($id)
    {
        $schedule = $this->repo->getById($id);

        return $schedule;
    }

    public function getSchedules($filters = [])
    {
        return $this->repo->getSchedules($filters);
    }

    /**
     * get print(pdf) of schedule
     *
     * @param $schedule [instance of Schedule]
     * @return pdf
     */
    public function printSchedule($id, $input = [])
    {
        $query = $this->repo->getSchedules();

        $schedule = $query->where('schedule_recurrings.id', $id)->first();

        $view = 'jobs.job-schedule';
        $fileName = 'job_schedule.pdf';

        if ($schedule->type == JobSchedule::EVENT_TYPE) {
            $view = 'calendarEvent.single';
            $fileName = 'event.pdf';
        }
        $company = $schedule->company;

        $contents = view($view, [
            'schedule' => $schedule,
            'company' => $company,
            'company_country_code' => $company->country->code
        ])->render();

        $pdf = $this->makePdf($contents);

        if (!ine($input, 'save_as_attachment')) {
            return $pdf->stream($fileName);
        }

        $attachment = $this->saveAsAttachment($pdf, $fileName);
        return $attachment;
    }

    /**
     * get print(pdf) of multiple schedulea
     *
     * @param $input [array]
     * @return pdf
     */
    public function printMultipleSchedules($filters = [])
    {

        if (!ine($filters, 'type')) {
            $filters['type'] = JobSchedule::SCHEDULE_TYPE;
        }
        $fileName = 'job_schedules.pdf';

        $contents = $this->getMulitpleScheduleContents($filters);
        // echo $contents;
        // $html = "<ht";

        // PDF::loadHTML("<h1>Hello</h1>")->setPaper('a4')->setOrientation('landscape')->setOption('margin-bottom', 0)->save('myfile1.pdf');
        // $contents = "<h1>Hello</h1>";

        $pdf = $this->makePdf($contents);
        $pdf->setOrientation('landscape');

        if ($filters['type'] == JobSchedule::EVENT_TYPE) {
            $fileName = 'events.pdf';
        }

        if (!ine($filters, 'save_as_attachment')) {
            return $pdf->stream($fileName);
        }

        $attachment = $this->saveAsAttachment($pdf, $fileName);
        return $attachment;
    }

    public function printUnscheduleJobs($filters = [])
    {
        $jobs = $this->jobRepo->getFilteredJobs($filters);
        $trades = Trade::pluck('name', 'id')->toArray();
        $company_id = $this->scope->id();
        $company = Company::find($company_id);
        $users = User::where('company_id', $company_id)
            ->select('id', DB::raw("CONCAT(first_name,' ',last_name) as fname"))
            ->pluck('fname', 'id')->toArray();
        $workflowRepo = App::make(WorkflowRepository::class);
        $stages = $workflowRepo->getActiveWorkflow($company_id)->stages->pluck('name', 'code')->toArray();
        $jobs = $jobs->with(
            'reps',
            'customer.address',
            'customer.address.state',
            'customer.address.country',
            'customer.phones',
            'customer.flags.color',
            'customer.rep',
            'address.state',
            'address.country',
            'trades',
            'workTypes',
            'jobWorkflow',
            'flags.color',
            'customer.secondaryNameContact'
        )->get();

        $contents = \view('jobs.unschedule_jobs_export', [
            'jobs' => $jobs,
            'users' => $users,
            'trades' => $trades,
            'stages' => $stages,
            'filters' => $filters,
            'company' => $company,
            'company_country_code' => $company->country->code
        ])->render();

        $pdf = $this->makePdf($contents);
        $pdf->setOrientation('landscape');

        if (!ine($filters, 'save_as_attachment')) {
            return $pdf->stream('unschedule_jobs.pdf');
        }
        $attachment = $this->saveAsAttachment($pdf, 'unschedule_jobs.pdf');
        return $attachment;
    }

    /**
     * Get job count
     * @param  array $filters [description]
     * @return [type]          [description]
     */
    public function getJobCount($filters = [])
    {
        $filters['stages'] = $filters['stage_code'];
        $job = $this->jobRepo->getFilteredJobs($filters);
        $job->excludeMultijobs();
        $data['total_jobs'] = $job->get()->count();
        $data['schedule_jobs'] = $job->has('schedules')->get()->count();
        $data['without_schedule_jobs'] = $data['total_jobs'] - $data['schedule_jobs'];

        $filters['projects_only'] = true;
        $projects = $this->jobRepo->getFilteredJobs($filters);
        $data['total_projects'] = $projects->get()->count();
        $data['schedule_projects'] = $projects->has('schedules')->get()->count();
        $data['without_schedule_projects'] = $data['total_projects'] - $data['schedule_projects'];

        return $data;
    }

    /**
     * Move Schedule
     * @param  Schedule $destSchedule Destincation Schedule
     * @param  String $startDateTime Start Date Time
     * @param  String $endDateTime [description]
     * @return Schedule
     */
    public function move($destSchedule, $startDateTime, $endDateTime, $fullDay = false)
    {
        $job = $destSchedule->job;
        $title = $destSchedule->title;

        $queueData = [
            'old_schedule_id' => $destSchedule->id,
            'current_user_id' => Crypt::encrypt(\Auth::id()),
        ];
        $meta = [
            'subject_edited' => $destSchedule->subject_edited,
            'trade_ids' => $destSchedule->trades->pluck('id')->toArray(),
            'work_type_ids' => $destSchedule->workTypes->pluck('id')->toArray(),
            'rep_ids' => $destSchedule->reps->pluck('id')->toArray(),
            'sub_contractor_ids' => $destSchedule->subContractors->pluck('id')->toArray(),
            'series_id' => $destSchedule->series_id,
            'work_crew_note_ids' => $destSchedule->workCrewNotes->pluck('id')->toArray(),
            'job_id' => $destSchedule->job_id,
            'type' => $destSchedule->type,
            'description' => $destSchedule->description,
            'work_order_ids' => $destSchedule->workOrders->pluck('id')->toArray(),
            'material_list_ids' => $destSchedule->materialLists->pluck('id')->toArray(),
            'full_day' => $fullDay
        ];

        $schedule = $this->repo->saveSchedule($title, $destSchedule->created_by, $meta);
        ScheduleRecurring::whereId($destSchedule->recurring_id)->first()->delete();

        //delete schedule
        $destSchedule = JobSchedule::find($destSchedule->id);
        $meta['old_recurring_id'] = $destSchedule->recurring_id;
        if (!$destSchedule->recurrings->count()) {
            $destSchedule->deleteAll();
        }

        $data = [
            'start_date_time' => $startDateTime,
            'end_date_time' => $endDateTime,
            'schedule_id' => $schedule->id
        ];

        $recurring = ScheduleRecurring::create($data);
        $schedule = $this->getById($recurring->id);

        $queueData['schedule_id'] = $schedule->id;
        $data['old_schedule_id'] = $destSchedule->id;
		$scheduleReps = $this->repo->saveRepsAndSubcontractor($schedule, $meta, $data);

        // move schedule on google calendar
        Queue::push('App\Services\JobSchedules\JobSchedulesQueueHandler@moveScheduleOnGoogleCalendar', $queueData);

        return $schedule;
    }

    /**
     * Get nearest Date
     * @param  Array $filters Filters
     * @return Response
     */
    public function getNearestDate($filters)
    {
        if (!ine($filters, 'job_id') && !ine($filters, 'customer_id')) {
            return null;
        }

        return $this->repo->getNearestDate($filters);
    }

    /**
     * Attach work orders Or material list
     * @param  Instance $schedule Schedule
     * @param  array $ids Array of work order ids Or material list ids
     * @param  type $type defines type wether Material List Or Work Order
     * @return Response
     */
    public function attachWorkOrdersOrMaterialLists($schedule, $ids = [], $type)
    {
        $this->repo->attachWorkOrdersOrMaterialLists($schedule, arry_fu($ids), $type);

        return true;
    }

    /**
     * Detach work orders
     * @param  Instance $schedule Schedule
     * @param  array $workOrderIds array of work order ids
     * @return Response
     */
    public function detachWorkOrders($schedule, $workOrderIds = [])
    {
        return $this->repo->detachWorkOrders($schedule, $workOrderIds);
    }


    /**
    * mark as completed schedule
    * @param $id (schedule id)
    * @param array of $inputs
    * @return true on success
    */
    public function markAsCompleted($id, $inputs)
    {
        $completedAt = ine($inputs, 'is_completed') ? Carbon::now() : null;
        $impactType = ine($inputs, 'impact_type') ? $inputs['impact_type'] : null;
        $schedule = $this->repo->getById($id);

        if (ine($inputs, 'update_job_completion_date')) {
			$job = $schedule->job;
			$jobCompletionDate = $completedAt ? $completedAt->toDateString() : null;
			$job->update(['completion_date' => $jobCompletionDate]);
		}

        if($schedule->isRecurring() && ($impactType == 'only_this')) {
            $scheduleData = [
                'job_id' => $schedule->job_id,
                'title'  => $schedule->title,
                'description' => $schedule->description,
                'customer_id' => $schedule->customer_id,
                'created_by'  => $schedule->created_by,
                'full_day'    => $schedule->full_day,
                'type'        => $schedule->type,
                'repeat'       => null,
                'occurence'    => null,
                'interval'     => 1,
                'completed_at' => $completedAt,
                'series_id'    => $schedule->series_id,
                'trade_ids'    => $schedule->trades->pluck('id')->toArray(),
                'work_type_ids'      => $schedule->workTypes->pluck('id')->toArray(),
                'rep_ids'            => $schedule->reps->pluck('id')->toArray(),
                'sub_contractor_ids' => $schedule->subContractors->pluck('id')->toArray(),
                'work_crew_note_ids' => $schedule->workCrewNotes->pluck('id')->toArray(),
                'work_order_ids'     => $schedule->workOrders->pluck('id')->toArray(),
                'material_list_ids'  => $schedule->materialLists->pluck('id')->toArray(),
            ];
            $schedule = $this->updateMarkAsCompleted($schedule, 
                    $impactType, 
                    $scheduleData
                );
        } else {
            $schedule->completed_at = $completedAt;
            $schedule->save();
        }
        if ($schedule) {
            $data['schedule_id'] = $schedule->id;
        }
        if($schedule->type == JobSchedule::EVENT_TYPE) return $schedule;
        \Event::fire('JobProgress.Jobs.Events.JobScheduleUpdated', new JobScheduleUpdated($schedule));
        return $schedule;
    }
    
    /**
     * update mark as completed schedule
     * @param $scheduleId, $impactType
     * 
     * @return $schedule
     */
    public function updateMarkAsCompleted($schedule, $impactType, $scheduleData)
    {
        $oldScheduleId = $schedule->id;
        $scheduleData['old_recurring_id'] = $schedule->recurring_id;
        $schedule->deleteRecurring();
        $newSchedule = $this->repo->saveSchedule($schedule->title, $schedule->created_by, $scheduleData);
        \DB::table('schedule_recurrings')
            ->insert([
                'schedule_id'     => $newSchedule->id,
                'start_date_time' =>  $schedule->start_date_time,
                'end_date_time'   => $schedule->end_date_time,
            ]);
        $schedule = $this->repo->getFirstRecurringSchedule($newSchedule->id);
        // update schedule on google calendar
        $data['old_schedule_id'] = $oldScheduleId;
        $data['schedule_id']     = $schedule->id;
        $data['current_user_id'] = \Crypt::encrypt(\Auth::id());
        $scheduleReps = $this->repo->saveRepsAndSubcontractor($newSchedule, $scheduleData, $data);
        \Queue::push('App\Services\JobSchedules\JobSchedulesQueueHandler@updateGoogleCalendar', $data);
        return $schedule;
    }

    public function importJobSchedule($title, $startDateTime, $endDateTime, $createdBy, $meta = array())
	{
		$schedule = $this->repo->saveSchedule($title, $createdBy, $meta);
		$schedule = $this->saveRecurringSchedule($schedule, $startDateTime, $endDateTime);

		return $schedule;
	}

    /**** Private Fuctions *****/

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

    /**
     * Save as attachment
     * @param  [type] $pdfObject [description]
     * @return [type]            [description]
     */
    private function saveAsAttachment($pdfObject, $name)
    {
        $rootDir = $this->getRootDir();
        $rootPath = \config('resources.BASE_PATH') . $rootDir->path;
        $physicalName = Carbon::now()->timestamp . '_' . $name;
        $filePath = $rootPath . '/' . $physicalName;
        $mimeType = 'application/pdf';
        // save pdf

        FlySystem::put($filePath, $pdfObject->output(), ['ContentType' => $mimeType]);

        $size = FlySystem::getSize($filePath);
        $resourcesRepo = App::make(ResourcesRepository::class);
        $resource = $resourcesRepo->createFile($name, $rootDir, $mimeType, $size, $physicalName);

        return $resource;
    }

    private function getRootDir()
    {
        $parentDir = Resource::name(Resource::EMAIL_ATTACHMENTS)
            ->company($this->scope->id())
            ->first();

        if (!$parentDir) {
            $resourceService = App::make(ResourceServices::class);
            $root = Resource::companyRoot($this->scope->id());
            $parentDir = $resourceService->createDir(Resource::EMAIL_ATTACHMENTS, $root->id);
        }
        return $parentDir;
    }

    private function saveRecurringSchedule($schedule, $startDateTime, $endDateTime)
    {
        if ($schedule->isRecurring()) {
            $dates = $this->recurrService->getscheduleDates($schedule, $startDateTime, $endDateTime);
            ScheduleRecurring::insert($dates);
            $schedule = $this->repo->getFirstRecurringSchedule($schedule->id);
        } else {
            $recurring = ScheduleRecurring::firstOrNew(['schedule_id' => $schedule->id]);
            $recurring->start_date_time = $startDateTime;
            $recurring->end_date_time = $endDateTime;
            $recurring->save();
            $schedule = $this->repo->getById($recurring->id);
        }

        return $schedule;
    }

    private function updateRecurringSchedule($schedule, $startDateTime, $endDateTime, $oldOccurence)
    {
        if ($schedule->isRecurring()) {
            $scheduleId = $schedule->id;
            $dates = $this->recurrService->updateAllScheduleRecurring($schedule, $startDateTime, $endDateTime, $oldOccurence);
            ScheduleRecurring::insert($dates);

            $schedule = $this->repo->find($schedule->recurring_id);

            if (!$schedule) {
                $schedule = $this->repo->getLastRecurringSchedule($scheduleId);
            }

            if (!$schedule) {
                $destSchedule = JobSchedule::find($scheduleId);
                $destSchedule->deleteAll();
            }
        } else {
            ScheduleRecurring::whereScheduleId($schedule->id)
                ->whereNull('deleted_at')
                ->where('id', '!=', $schedule->recurring_id)
                ->forceDelete();

            $recurring = ScheduleRecurring::find($schedule->recurring_id);
            $recurring->start_date_time = $startDateTime;
            $recurring->end_date_time = $endDateTime;
            $recurring->save();
            $schedule = $this->repo->getById($recurring->id);
        }


        return $schedule;
    }

    /**
     * @param  Array $filters Filters
     * @return Contents
     */
    private function getMulitpleScheduleContents($filters)
    {
        $view = 'jobs.multiple-job-schedules';

        $company = Company::whereId($this->scope->id())->with('workTypes')->first();
        $query = $this->repo->getSchedules($filters);
        $users = User::where('company_id', $this->scope->id())
            ->select('id', DB::raw("CONCAT(first_name,' ',last_name) as fname"))
            ->pluck('fname', 'id')->toArray();

        $query->orderBy('start_date_time', 'asc');

        $data = [
            'company' => $company,
            'filters' => $filters,
            'users' => $users,
            'company_country_code' => $company->country->code
        ];
        if ($filters['type'] == JobSchedule::EVENT_TYPE) {
            $view = 'calendarEvent.multiple';
            $data['schedules'] = $query->get();
        } else {
            $data['workTypes'] = $company->workTypes()->pluck('name', 'id')->toArray();
            $data['schedules'] = $query->with('job', 'job.customer', 'job.trades', 'job.customer.secondaryNameContact')
                ->get();
            $data['trades'] = Trade::pluck('name', 'id')->toArray();
        }

        $contents = view($view, $data)->render();

        return $contents;
    }

    /**
     * save schedule reminders
     * @param  $schedule
     * @param  $reminderData
     * @return $schedule
     */
    private function saveReminders($schedule, $reminderData)
    {
        if($schedule->type == JobSchedule::EVENT_TYPE) return;
        if(!is_array($reminderData)) return $schedule;
        $schedule->reminders()->delete();
        if(empty($reminderData)) return $schedule;
        $data = [];
        $reminderData = array_unique($reminderData, SORT_REGULAR);
        foreach ($reminderData as $key => $value) {
            if(!ine($value, 'type') || !ine($value, 'minutes')) continue;
            $data[] = new ScheduleReminder ([
                'schedule_id'    => $schedule->id,
                'type'           => $value['type'],
                'minutes'        => $value['minutes'],
            ]);
        }
        if(!empty($data)) {
            $schedule->reminders()->saveMany($data);
        }
        return $schedule;
    }
}
