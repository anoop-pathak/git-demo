<?php

namespace App\Services\Sync;

use App\Helpers\SecurityCheck;
use App\Models\Address;
use App\Models\Appointment;
use App\Models\Email;
use App\Models\GoogleClient;
use App\Models\OnboardChecklistSection;
use App\Models\Task;
use App\Services\Contexts\Context;
use App\Models\WorkflowStage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Request;
use App\Models\MessageThread;

class SyncService
{

    protected $scope;

    protected $totalSection;

    function __construct(Context $scope)
    {
        $this->scope = $scope;
    }

    /**
     * Get Sync data
     * @return array | Array of sync data
     */
    public function getSync()
    {
        $data = [];

        if (!$this->scope->has()) {
            return $data;
        }

        $data['pending_tasks_count'] = $this->pendingTasks();
        $data['unread_messages_count'] = $this->unreadMessages();
        $data['unread_text_messages_count'] = $this->unreadTextMessages();
        $data['unread_notifications_count'] = $this->unreadNotfications();
        $data['upcoming_appointments_count'] = $this->upcomingAppointments();
        $data['daily_plans_count'] = $this->dailyPlans();
        $data['workflow_stages_with_jobs_count'] = $this->workflowStagesWithJobCounts(true);
        $data['nearby_feature'] = $this->nearbyFeature();
        $data['is_restricted'] = SecurityCheck::RestrictedWorkflow();
        $data['unread_emails_count'] = $this->getUnreadEmail();

        $isCompanyGoogleAccountConnected = GoogleClient::whereCompanyId($this->scope->id())
            ->exists();
        $data['company_google_account'] = $isCompanyGoogleAccountConnected;

        if (\Auth::user()->isOwner()) {
            $data['total_section_count'] = $this->getTotalSectionCount();
            $data['completed_section_count'] = $this->getCompletedSectionCount();
        }

        return $data;
    }

    /**
     * Pending Tasks counts
     * @return int | count
     */
    public function pendingTasks()
    {
        return Task::pending()->assignedTo(Auth::id())->division()->count();
    }

    /**
     * Unread Messages counts
     * @return int | count
     */
    public function unreadMessages()
    {
        $filters = [
			'message_type' => MessageThread::TYPE_SYSTEM_MESSAGE
		];
        $service = App::make(\App\Services\Messages\MessageService::class);

        return $service->getUnreadMessagesCount(Auth::id(), $filters);
    }

    /**
	 * Unread Messages counts
	 * @return int | count
	 */
	public function unreadTextMessages() {
		$filters = [
			'message_type' => MessageThread::TYPE_SMS
		];
		$service = App::make(\App\Services\Messages\MessageService::class);

        return $service->getUnreadMessagesCount(Auth::id(), $filters);
    }

    /**
     * Unread Notifications counts
     * @return int | count
     */
    public function unreadNotfications()
    {
        return \Auth::user()->notifications()->count();
    }

    /**
     * Upcoming Appointment counts
     * @return int | count
     */
    public function upcomingAppointments()
    {
        return Appointment::recurring()->where('company_id', getScopeId())->upcoming()->current()->count();
    }

    /**
     * Daily Plans counts
     * @return int | count
     */
    public function dailyPlans()
    {
        $tasks = Task::today()->pending()->assignedTo(\Auth::id())->division()->count();
        $appointments = Appointment::recurring()->where('company_id', getScopeId())->today()->current()->count();
        $count = $tasks + $appointments;
        return $count;
    }

    /**
     * Workflow stages with jobs count
     * @return array | Array of stages
     */
    public function workflowStagesWithJobCounts($blankArry = false)
    {
        $stages = [];

        if($blankArry) {
            // change db connection for report
            switchDBConnection('mysql2');
            $filters = Request::onlyLegacy('stage_code','user_id', 'division_ids', 'trades', 'date_range_type', 'start_date', 'end_date', 'insurance_jobs_only');
            $includeTotalJobAmount = false;
            $stages = WorkflowStage::getStagesWithJobCountAndAmount($filters['user_id'], $filters, $includeTotalJobAmount);
            switchDBConnection('mysql');
        }

        return $stages;
    }

    /**
     * Nearby feature required
     * @return boolean
     */
    public function nearbyFeature()
    {
        return Address::isDistanceCalculationPossible();
    }

    /**
     * get unread count
     * @return [int] [uncread count]
     */
    public function getUnreadEmail()
    {
        $service = App::make(\App\Services\Emails\EmailServices::class);
        return $service->getUnreadEmailCount();
    }

    /**
     * Get total checklist count
     * @return count
     */
    public function getTotalSectionCount()
    {
        $this->totalSection = OnboardChecklistSection::has('checklists')->count();

        return $this->totalSection;
    }

    /**
     * Get Company Selected checklist count
     * @return count
     */
    public function getCompletedSectionCount()
    {
        $repo = App::make(\App\Repositories\OnboardChecklistSectionRepository::class);

        return $this->totalSection - $repo->getUncompletedSectionCount();
    }
}
