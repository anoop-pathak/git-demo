<?php

namespace App\Services\Firebase;

use App\Models\Task;
use App\Models\User;
use App\Models\Email;
use App\Models\Company;
use MobileNotification;
use Firebase\FirebaseLib;
use App\Helpers\SecurityCheck;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Repositories\EmailsRepository;
use App\Repositories\TimeLogRepository;
use App\Services\Messages\MessageService;
use GuzzleHttp\Client as GuzzleClient;
use App\Exceptions\FirebaseException;
use App\Models\MessageThread;

class Firebase
{

    protected $userId = null;
    protected $user = null;

    /**
     * Company databsae setup
     * @param  [type] $companyId [description]
     * @return [type]            [description]
     */
    public function companyDatabaseSetup($companyId)
    {
        $data = [
            'workflow_updated' => uniqueTimestamp(),
            'activity_feed_updated' => uniqueTimestamp(),
            'company_setting_updated_at' => uniqueTimestamp()
        ];
        $companyUrl = 'company/' . $companyId;

        $this->update($companyUrl, $data);
    }

    /**
     * Company user database setup
     * @param  User $user [description]
     * @return [type]       [description]
     */
    public function userDatabaseSetup(User $user)
    {
        $this->user = $user;
        //get Data
        $countData = [
            'pending_tasks_count' => 0,
            'unread_messages_count' => 0,
            'unread_emails_count' => 0,
            'unread_notifications_count' => 0,
        ];

        $settingData = [
            'is_restricted' => $this->checkRestricatedWorkflow()
        ];

        $userMeta = [
            'today_appointment_updated' => uniqueTimestamp(),
            'today_task_updated' => uniqueTimestamp(),
            'permissions_updated' => uniqueTimestamp(),
            'upcoming_tasks_updated' => uniqueTimestamp(),
            'upcoming_appointments_updated' => uniqueTimestamp(),
        ];

        //set url
        $userUrl = 'company/' . $user->company_id . '/users/' . $user->id;
        $userCountUrl = $userUrl . '/count';
        $userSettingUrl = $userUrl . '/settings';

        //send firebase request
        $this->update($userCountUrl, $countData);
        $this->update($userSettingUrl, $settingData);
        $this->update($userUrl, $userMeta);

        $this->user = null;
        $this->userId = null;

        return true;
    }

    /**
     * Update User Unread Task Count
     * @param  [type] $userId [description]
     * @return VOID
     */
    public function updateUserTaskCount($userId)
    {
        $this->userId = $userId;
        $data['pending_tasks_count'] = $this->getPandingTaskCount();

        $this->update($this->getUserUrl('count'), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Update unread message count of User
     * @param Int User Id
     * @return VOID
     */
    public function updateUserMessageCount($userId)
    {
        $this->userId = $userId;
        $data['unread_messages_count'] = $this->getUnreadMessageCount();

        $this->update($this->getUserUrl('count'), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
	 * Update unread text message count of User
	 * @param Int User Id
	 * @return VOID
	 */
	public function updateUserTextMessageCount($userId)
	{
		$this->userId = $userId;
		$data['unread_text_messages_count'] = $this->getUnreadTextMessageCount();

		$this->update($this->getUserUrl('count'), $data);

		$this->user = null;
		$this->userId = null;
	}

    /**
     * Update unread notification count of user
     * @param Int User Id
     * @return VOID
     */
    public function updateUserNotificationCount(User $user)
    {
        $this->user = $user;
        $data['unread_notifications_count'] = $this->getUnreadNotificatonCount();

        $this->update($this->getUserUrl('count'), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Update Unread email count of user
     * @param Int User Id
     * @return VOID
     */
    public function updateUserEmailCount($userId)
    {
        $this->userId = $userId;
        $data['unread_emails_count'] = $this->getUnreadEmail();

        MobileNotification::sendHiddenNotification($userId, $data['unread_emails_count']);

        $this->update($this->getUserUrl('count'), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Set Flag of workflow update for user
     * @param Int User Id
     * @return VOID
     */
    public function updateWorkflow()
    {
        $data['workflow_updated'] = uniqueTimestamp();

        $this->update($this->getCompanyUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }


    /**
     * Update User settings
     * @param Int User Id
     * @return VOID
     */
    public function updateUserSettings(User $user)
    {
        $this->user = $user;
        $data['is_restricted'] = $this->checkRestricatedWorkflow();

        $this->update($this->getUserUrl('settings'), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Update company activity feed timestamp
     * @return VOID
     */
    public function updateActivityFeed()
    {
        $data['activity_feed_updated'] = uniqueTimestamp();

        $this->update($this->getCompanyUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
	 * Update company setting timestamp
	 * @return VOID
	 */
	public function updateCompanySetting($userId)
	{
		$this->userId = $userId;
		if($this->userId) {
			$data['settings_updated_at'] = uniqueTimestamp();
			$this->update($this->getUserUrl(), $data);
		}else {
			$data['company_setting_updated_at'] = uniqueTimestamp();
			$this->update($this->getCompanyUrl(), $data);

		}

		$this->user = null;
		$this->userId = null;
	}

    /**
     * Update today appointment timestamp of user
     * @param  Int $userId id of user
     * @return Void
     */
    public function updateTodayAppointment($userId)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $this->userId = $userId;
        $data['today_appointment_updated'] = uniqueTimestamp();

        $this->update($this->getUserUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Update Today Task
     * @param  Int $userId User Id
     * @return Void
     */
    public function updateTodayTask($userId)
    {
        $this->userId = $userId;
        $data['today_task_updated'] = uniqueTimestamp();

        $this->update($this->getUserUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Set to firebase update request through queue
     * @param  URl $url Url
     * @param  Data $data Data
     * @return Void
     */
    public function update($key, $data = [])
    {
        // $data = [
        // 	'key'  => $key,
        // 	'data' => $data
        // ];

        // Queue::push(\App\Services\Firebase\FirebaseQueueHandler::class, $data);
        try {
            $firebase = new FirebaseLib(config('firebase.url'), config('firebase.database_secret'));
            $firebase->update($key, $data);
        } catch (\Symfony\Component\Debug\Exception\FatalErrorException $e) {
            //handle exception maximum timeout

            Log::error($e);
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    /**
     * Update user permission
     * @param  [type] $userId [description]
     * @return [type]         [description]
     */
    public function updateUserPermissions($userId)
    {
        $this->userId = $userId;
        $data['permissions_updated'] = uniqueTimestamp();

        $this->update($this->getUserUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Update user upcoming appointment timestamp on firebase
     * @param  Int $userId User id
     * @return Void
     */
    public function updateUserUpcomingAppointments($userId)
    {
        if(!config('notifications.enabled')) {
			return true;
		}
        $this->userId = $userId;
        $data['upcoming_appointments_updated'] = uniqueTimestamp();

        $this->update($this->getUserUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Update user upcoming task timestamp on firebase
     * @param  Int $userId User Id
     * @return Void
     */
    public function updateUserUpcomingTasks($userId)
    {
        $this->userId = $userId;
        $data['upcoming_tasks_updated'] = uniqueTimestamp();

        $this->update($this->getUserUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }

    /**
     * Update User CheckIn
     * @param  Int $userId User Id
     * @return Void
     */
    public function updateUserCheckIn($userId)
    {
        $this->userId = $userId;
        $repo = App::make(TimeLogRepository::class);

        $checkIn = $repo->getCheckInLogByUserId($userId);
        $jobId = ($checkIn) ? $checkIn->job_id : null;
        $withoutJob = ($checkIn) ? (int)(!$jobId) : 0;

        $data['check_in_job'] = $jobId;
        $data['check_in_without_job'] = $withoutJob;

        $this->update($this->getUserUrl(), $data);

        $this->user = null;
        $this->userId = null;
    }

    public function getShortUrl($longUrl){
		$path = 'shortLinks';
		$apiKey = config('firebase.api_key');
		$body = [
			'dynamicLinkInfo'=>[
				'domainUriPrefix'=>config('firebase.domain'),
				'link' => $longUrl,
			]
		];
		try{
			$request = new GuzzleClient(['base_url' => config('firebase.base_api_url'), 'debug' => true]);
			$request->setDefaultOption('verify', false);
			$url = config('firebase.base_api_url').$path;
			if(!empty($apiKey)) {
				$url .= '?key='.$apiKey;
			}
			$requestParams = $request->createRequest('Post',
				$url,
				['json' => $body]
			 );
			$response = $request->send($requestParams);
			$result = $response->getBody()->getContents();
			$shortUrl = json_decode($result, true);
			if(!ine($shortUrl, 'shortLink')) {
				throw new FirebaseException("Short URL has not created, Please try again.");
			}
			return $shortUrl;
		} catch(\Exception $e){
			$errorDetail = $e->getLine() .' ' .$e->getFile() . ' '.$e->getMessage();
			switch ($e->getCode()) {
				case 400:
					Log::error('Firebase Error');
					Log::error($errorDetail);
					throw new FirebaseException(trans('response.error.something_wrong'));
				break;
				default:
					Log::error('Firebase Error');
					Log::error($errorDetail);
					throw new FirebaseException(trans('response.error.something_wrong'));
				break;
			}
		}
	}

    /********************* PRIVATE METHOD *****/

    /**
     * Get  Company Url
     * @return [type] [description]
     */
    private function getCompanyUrl()
    {

        return 'company/' . $this->getCompanyId();
    }

    /**
     * Get Company Id
     * @return Company Id
     */
    private function getCompanyId()
    {
        $scope = App::make(Context::class);

        if ($scope->has()) {
            return $scope->id();
        }

        if ($this->user) {
            return $this->user->company_id;
        }


        return 0;
    }

    /**
     * Get User Url
     * @param  String $key slug name
     * @return User Url
     */
    private function getUserUrl($key = null)
    {
        if ($this->user) {
            $this->userId = $this->user->id;
        }

        $url = $this->getCompanyUrl() . '/users/' . $this->userId;

        if ($key) {
            $url .= '/' . $key;
        }

        return $url;
    }

    /**
     * Get Panding Task Count
     * @return int value of panding task count
     */
    private function getPandingTaskCount()
    {
        $pandingTaskCount = Task::pending()->assignedTo($this->userId)->count();

        return $pandingTaskCount;
    }

    /**
     * Get Unread Message count
     * @return Int value Unread message count
     */
    private function getUnreadMessageCount()
    {
        $filters = [
			'message_type' => MessageThread::TYPE_SYSTEM_MESSAGE
		];
        $service = App::make(MessageService::class);

        return $service->getUnreadMessagesCount($this->userId, $filters);
    }

    /**
	 * Get Unread text Message count
	 * @return Int value Unread text message count
	 */
	private function getUnreadTextMessageCount()
	{
		$filters = [
			'message_type' => MessageThread::TYPE_SMS
		];
		$service = App::make(MessageService::class);

        return $service->getUnreadMessagesCount($this->userId, $filters);
    }

    /**
     * Get Unread Nofification count
     * @return Int value of unread
     */
    private function getUnreadNotificatonCount()
    {

        return $this->user->notifications()->count();
    }

    /**
     * Get Unread Email count
     * @return int value of unread email
     */
    private function getUnreadEmail()
    {
        $repo = App::make(EmailsRepository::class);

        return $repo->getEmails([
            'type' => Email::UNREAD,
            'with_reply' => true,
            'users' => (array)$this->userId,
            'not_moved' => true,
        ])->get()->count();
    }

    /**
     * Check Restricated workflow
     * @return boolean
     */
    private function checkRestricatedWorkflow()
    {

        return SecurityCheck::RestrictedWorkflow($this->user);
    }
}
