<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\User;
use MobileNotification;
use PushNotification;
use Request;
use Queue;
use Illuminate\Support\Facades\Validator;

class PushNotificationController extends ApiController
{

    public function sendPushNotification()
    {
        $input = Request::onlyLegacy('title', 'message');
        $validator = Validator::make($input, ['title' => 'required', 'message' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $ids = User::authority()->pluck('id')->toArray();
        if (empty($ids)) {
            return false;
        }
        MobileNotification::send($ids, $input['title'], 'type', $input['message']);

        return ApiResponse::success(['message' => 'Notification send successfully.']);
    }

    public function sendIosNotification()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['token' => 'required', 'badge' => 'required_if:hidden,1']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $title = ine($input, 'title') ? $input['title'] : 'developer_testing';
        $messageString = ine($input, 'message') ? $input['message'] : 'developer_testing_message';
        $badge = ine($input, 'badge') ? $input['badge'] : 0;
        $queue = ine($input, 'queue');

        if ($queue) {
            $messageString .= " (Queue)";
        }

        $body = [];
        try {
            $data = [
                'title' => $title,
                'messageString' => $messageString,
                'badge' => $badge,
                'body' => $body,
                'token' => $input['token'],
                'input' => $input
            ];

            if ($queue) {
                Queue::push('PushNotificationController@sendIosQueueNotificatication', $data);
            } else {
                $this->sendIosNotificatication($data);
            }

            return ApiResponse::success([
                'message' => 'Push notification send successfully.',
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage(), $e);
        }
    }


    public function sendIosQueueNotificatication($queueJob, $data)
    {
        $this->sendIosNotificatication($data);
        $queueJob->delete();
    }

    public function sendIosNotificatication($data)
    {
        $message = [
            'badge' => $data['badge'],
            'body' => $data['messageString'],
            'title' => $data['title'],
            'custom' => [
                'title' => $data['title'],
                'type' => 'type',
                'data' => json_encode($data['body'], true)
            ]
        ];

        if (ine($data['input'], 'hidden')) {
            $data['messageString'] = false;
            $message = [
                'badge' => $data['badge']
            ];
        }

        $devices = PushNotification::DeviceCollection([
            PushNotification::Device($data['token'])
        ]);

        $message = PushNotification::Message($data['messageString'], $message);

        $push = PushNotification::app('appNameIOS')
            ->to($devices)
            ->send($message);
    }

    public function iosNotificationNewYear()
    {
        $Chunk1st = [13,14,15,16,17,18,19,22,23,24];
        $Chunk2nd = [25,26,27,29,30,31,32,34,35,36];
        $Chunk3rd = [37,38,39,40,41,42,43,44,49,50];
        $Chunk4th = [55,56,57,58,59,60,61,62,63,64];
        $Chunk5th = [65,66,67,68,69,70,71,72,73,74];
        $Chunk6   = [75,76,77,78,79,80,81,82,83,84];
        $Chunk7   = [85,86,87,88,89,90,91,92,105,106,107];
        $Chunk8   = [108,109,110,111,112,113,114,115,116,117];
        $Chunk9   = [118,119,130,131,132,133,134,135,136,138];
        $Chunk10  = [139,140,141,142,143,144,145,146,147,148];
        $Chunk11  = [149,150,151,152,153,154,155,156,157,158];
        $Chunk12  = [167,168,169,170,171,172,173,174,175,176];
        $Chunk13  = [177,178,179,180,181,182,183,184,185,186];
        $Chunk14  = [187,188,189,190,191,192,193,194,195,196];
        $Chunk15  = [197,198,199,200,201,202,203,204,205,206];
        $Chunk16  = [207,208,209,210,211,212,213,214,215,216];
        $Chunk17  = [217,218,219,220,221,222,223,224,225,226];
        $Chunk18  = [227,228,229,230,231,232,233,234,235,236];
        $Chunk19  = [237,238,239,240,241,242,243,244,245,246];
        $Chunk20  = [247,248,249,250,251,252,253,254,255];
        $this->sendIos($Chunk1st);
        $this->sendIos($Chunk2nd);
        $this->sendIos($Chunk3rd);
        $this->sendIos($Chunk4th);
        $this->sendIos($Chunk5th);
        $this->sendIos($Chunk6);
        $this->sendIos($Chunk7);
        $this->sendIos($Chunk8);
        $this->sendIos($Chunk9);
        $this->sendIos($Chunk10);
        $this->sendIos($Chunk11);
        $this->sendIos($Chunk12);
        $this->sendIos($Chunk13);
        $this->sendIos($Chunk14);
        $this->sendIos($Chunk15);
        $this->sendIos($Chunk16);
        $this->sendIos($Chunk17);
        $this->sendIos($Chunk18);
        $this->sendIos($Chunk19);
        $this->sendIos($Chunk20);
    }
    private function sendIos($userIds)
    {
        $title =  "Happy New Year's from JobProgress!";
        $type  = "announcement";
        $message = "Prepare for an AWESOME 2019 with JP! Many new features, faster load times, more flexibility ... MAJOR UPDATE scheduled before 2019 and many more for the New Year! SPECIAL OFFER: Add at least 2 USERS, refer any 2 CONTRACTOR leads, request any new FORM or Proposal Template to david@jobprogress.com before Jan 1, 2019 and RECEIVE a 0 CREDIT in January as a THANK YOU! for helping JP help YOU build and run a BETTER BUSINESS!";
        $data = [
            'user_ids'       => $userIds,
            'title'          => $title,
            'type'           => $type,
            'message_string' => $message,
            'meta'           => [],
        ];
        Queue::push('App\Services\PushNotification\MobileNotification@ios', $data);
    }
}
