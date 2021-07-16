<?php

namespace App\Services\PushNotification;

use App\Models\MobileApp;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Log;
use Queue;
use PushNotification;
use App\Models\User;

class MobileNotification
{
    /**
     * Ios Notification
     * @param  Object $job Queue Object
     * @param  Array $data Array data
     * @return Void
     */
    public function ios($job, $data)
    {
        try {
            $tokens = UserDevice::whereIn('user_id', (array)$data['user_ids'])
                ->where('platform', '=', MobileApp::IOS)
                ->where('company_id', '!=', 1949)
                ->whereNotNull('device_token');
            if(ine($data, 'multi_user_notification')) {
                $tokens->where('app_version', '>', '2.5.0');
            } else {
                $tokens->where('app_version', '<=', '2.5.0');
            }
            $tokens = $tokens->pluck('device_token')->toArray();

            $tokens = array_unique(array_filter($tokens));
            if (empty($tokens)) {
                $job->delete();

                return;
            }

            $collection = [];
            foreach ($tokens as $key => $token) {
                $collection[$key] = PushNotification::Device($token);
            }

            $devices = PushNotification::DeviceCollection($collection);

            if(isset($data['message_string'])) {
                $messageString = $data['message_string'];
                $message = [
                    'body' => $messageString,
                    'title' => $data['title'],
                    'custom' => [
                        'title' => $data['title'],
                        'type' => $data['type'],
                        'data' => json_encode($data['meta'], true)
                    ]
                ];
            } else {
                $messageString = false;
                $message = [
                    'badge' => $data['badge']
                ];
            }

            $message = PushNotification::Message($messageString, $message);

            $push = PushNotification::app('appNameIOS')
                ->to($devices)
                ->send($message);
            $job->delete();
        } catch (\Exception $e) {
            if ($job->attempts() > 3) {
                $job->delete();
                Log::error('Ios Queue Push Notification After 3 Attempts: ' . getErrorDetail($e));
            } else {
                Log::error('Ios Queue Push Notification: ' . getErrorDetail($e));
            }
        }
    }

    /**
     * Android Notification
     * @param  Object $job Queue Object
     * @param  Array $data Array data
     * @return Void
     */

    public function android($job, $data)
    {
        try {
            $tokens = UserDevice::whereIn('user_id', (array)$data['user_ids'])
                ->whereNotNull('device_token')
                ->where('company_id', '!=', 1949)
                ->where('platform', '=', MobileApp::ANDROID);

            if(ine($data, 'multi_user_notification')) {
                $tokens->where('app_version', '>', '2.5.0');
            } else {
                $tokens->where('app_version', '<=', '2.5.0');
            }
            $tokens = $tokens->pluck('device_token')->toArray();

            $tokens = array_unique(array_filter($tokens));
            if (empty($tokens)) {
                $job->delete();

                return;
            }

            $collection = [];
            foreach ($tokens as $key => $token) {
                $collection[$key] = PushNotification::Device($token);
            }

            $devices = PushNotification::DeviceCollection($collection);
            $message = PushNotification::Message(
                $data['message_string'],
                [
                    'title' => $data['title'],
                    'type' => $data['type'],
                    'data' => $data['meta'],
                    'edata' => $data['meta']
                ]
            );
            $push = PushNotification::app('appNameAndroid')
                ->to($devices)
                ->send($message);

            $job->delete();
        } catch (\Exception $e) {
            if ($job->attempts() > 3) {
                $job->delete();
                Log::error('Android Queue Push Notification After 3 Attempts: ' . getErrorDetail($e));
            } else {
                Log::error('Android Queue Push Notification: ' . getErrorDetail($e));
            }
        }
    }

    /**
     * Send Notification
     * @param  Array $ids receipts Ids
     * @param  String $title notification title
     * @param  String $type notification type
     * @param  String $message message
     * @param  String $data meta information
     * @return Void
     */
    public function send($ids, $title, $type, $message, $data = [])
    {
        $androidNotification = UserDevice::whereIn('user_id', (array)$ids)
            ->whereNotNull('device_token')
            ->where('platform', 'LIKE', '%' . MobileApp::ANDROID . '%')
            ->where('company_id', '!=', 1949)
            ->count();

        $iosNotification = UserDevice::whereIn('user_id', (array)$ids)
            ->whereNotNull('device_token')
            ->where('platform', 'LIKE', '%' . MobileApp::IOS . '%')
            ->where('company_id', '!=', 1949)
            ->count();

        $this->sendNotificationsToMultiUser($ids, $title, $type, $message, $data);

        if (!$androidNotification && !$iosNotification) {
            return;
        }

        $data = [
            'user_ids' => $ids,
            'title' => $title,
            'type' => $type,
            'message_string' => $message,
            'meta' => $data
        ];

        if ($androidNotification) {
            Queue::push('App\Services\PushNotification\MobileNotification@android', $data);
        }

        if ($iosNotification) {
            Queue::push('App\Services\PushNotification\MobileNotification@ios', $data);
        }
    }

    /**
     * Send Hidden Notification
     * @param  Int $userId User Id
     * @param  Int $badge Badge Count
     * @return Boolean
     */
    public function sendHiddenNotification($userId, $badge)
    {
        $data = [
            'badge' => $badge,
            'user_ids' => $userId
        ];

        Queue::push('App\Services\PushNotification\MobileNotification@ios', $data);
    }


	/********** Private Functions **********/
	private function sendNotificationsToMultiUser($ids, $title, $type, $message, $data = array())
	{
		$ids = User::whereIn('email', function($query) use($ids) {
			$query->select('email')
				->from('users')
				->whereIn('id', (array)$ids);
        })->pluck('id')->toArray();

		if(empty($ids)) return true;
		$data = [
			'user_ids'       => $ids,
			'title'          => $title,
			'type'           => $type,
			'message_string' => $message,
			'meta'           => $data,
			'multi_user_notification' => true,
		];
		Queue::push('App\Services\PushNotification\MobileNotification@android', $data);
		Queue::push('App\Services\PushNotification\MobileNotification@ios', $data);
	}
}
