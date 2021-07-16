<?php
namespace App\Repositories;

use App\Events\NewMessageEvent;
use App\Models\PhoneMessageMedia;
use App\Models\Message;
use App\Models\MessageThread;
use FlySystem;
use App\Exceptions\PhoneMessageException;
use App\Models\TwilioNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

Class PhoneMessageRepository extends ScopedRepository
{
    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $messageThread;

    public function __construct(MessageThread $messageThread)
    {
        $this->messageThread = $messageThread;
    }

    /**
     * Get parent Message node.
     *
     * @param String $phoneNumber
     * @return MessageThread Model instance.
     */
    public function getParentMessageForReply($phoneNumber, $userId)
    {
        $query = $this->messageThread->where('phone_number', $phoneNumber)->where('created_by', $userId)->orderBy('id','desc');

        return $query->first();
    }

    /**
     * Get list of messages
     *
     * @param Boolean   $sortable: default true
     * @param Array     $params: array of filtering params.
     * @return Collection of PhoneMessage
     */
    public function getMessages($sortable = true, $params = array())
    {
        $messages = null;

        $includeData = $this->includeData($params);

        if($sortable){
            $messages = $this->make($includeData)->sortable();
        }else{
            $messages = $this->make($includeData);
        }

        return $messages;
    }

    /**
     * create messsage
     * @return message
     */
    public function createMessages($messageData, $threadId, $metaData = array())
    {
        $userParticipants = DB::table('message_thread_participants')->where('thread_id', $threadId)
                ->where('ref_type', MessageThread::USER_PARTICIPANTS)->get();

        foreach ($userParticipants as $key => $userParticipant) {
            $participant[] = $userParticipant->ref_id;
        }

        $authUserParticipant = last($participant);
        $data = [
            'sender_id'     => ine($messageData, 'sender_id') ? $messageData['sender_id'] : null,
            'company_id'    => ine($messageData, 'company_id') ? $messageData['company_id'] : getScopeId(),
            'subject'       => null,
            'content'       => $this->getBodyContent($messageData, $metaData),
        	'sms_id'        => $messageData['sid'],
            'sms_status'    => Message::getSMSStatus($messageData['status']),
            'customer_id'   => $this->getCustomerId($messageData, $metaData),
            'thread_id'     => $threadId,
        ];

    	$messageObj = Message::create($data);

        $upperCaseStatus = strtoupper($messageData['status']);
        if($upperCaseStatus == Message::SMS_STATUS_RECEIVED) {

            setAuthAndScope($authUserParticipant);

            $messageObj->status()->attach($participant, [
                'status'    => Message::UNREAD,
                'thread_id' => $threadId
            ]);

        } else {
            $removeAuthUser = array_pop($participant);
            $messageObj->status()->attach($participant, [
                'status'    => Message::UNREAD,
                'thread_id' => $threadId
            ]);

            $messageObj->status()->attach($authUserParticipant, [
                'status'    => Message::READ,
                'thread_id' => $threadId
            ]);
        }

        // save reply media in Database
        if(ine($messageData, 'media_urls')) {
            $this->saveRepliedMediaInDB($messageObj->sms_id, $messageData);
        }

        // save sended media in Database
        if(ine($metaData, 'media_urls')){
            $companyId = getScopeId();
            $this->addMediaData($messageObj->sms_id, $companyId, $metaData['media_urls']);
        }

        //event for new message..
        Event::fire('JobProgress.Messages.Events.NewMessageEvent', new NewMessageEvent($messageObj, $participant));

        return $messageObj;
    }

    /***************** Private Functions ***************/
    /**
     * Add Media in DB
     * @param $messageSid
     * @param $companyId
     * @param array  $mediaUrls
     */
    private function addMediaData($messageSid, $companyId, $mediaUrls = array())
    {
        foreach ($mediaUrls as $mediaUrl) {
            $data = [
                'sid'        => $messageSid,
                'company_id' => $companyId,
                'media_url'  => ine($mediaUrl, 'file_url')? $mediaUrl['file_url'] : null,
                'short_url'  => ine($mediaUrl, 'short_url')? $mediaUrl['short_url'] : null,
            ];
            PhoneMessageMedia::create($data);
        }
    }

    /**
     * save Media info in DB that is come in Reply of the message
     * @param  $messageData
     * @return
     */
    private function saveRepliedMediaInDB($messageSid, $messageData)
    {
        $companyId = $messageData['company_id'];
        foreach ($messageData['media_urls'] as $key => $value) {
            $file = $value['file'];
            $mimeType = $value['mime_type'];

            $fileName = $messageSid.'-'.$key;
            $url = parse_url($file);
            $path = ine($url, 'path') ? $url['path'] : null;

            if($path) {
                $fileWithExt = addExtIfMissing($file, $mimeType);
                $pathInfo = pathinfo($fileWithExt);
                $fileName = ine($pathInfo, 'basename') ? $pathInfo['basename'] : 'image';
            }

            $contents = file_get_contents($file);
            $newName =  generateUniqueToken().'_'.$fileName;

            $fullPath = "{$companyId}/$newName";

            FlySystem::connection('s3_attachments')->put($fullPath, $contents, ['ACL' => 'public-read']);

            $mediaUrls[]['file_url'] = FlySystem::connection('s3_attachments')->getUrl($fullPath);
        }

        $this->addMediaData($messageSid, $companyId, $mediaUrls);
    }

    /**
     * includeData
     * @param  Array $input | Input Array
     * @return Array
     */
    private function includeData($input = [])
    {
        $with = [];

        $includes = isset($input['includes']) ? $input['includes'] : [];
        if(!is_array($includes) || empty($includes)) return $with;

        if(in_array('customer', $includes)) {
            $with[] = 'customer';
        }

        return $with;
    }

    /**
     * Get Customer Id
     * @param  array $messageData
     * @param  array $metaData
     * @return int
     */
    private function getCustomerId($messageData, $metaData)
    {
        if(!ine($messageData, 'customer_id') && !ine($metaData, 'customer_id')) return null;

        if(ine($messageData, 'customer_id')) {
            return $messageData['customer_id'];
        }

        return $metaData['customer_id'];
    }

    /**
     * Get Body Content
     * @param  $messageData
     * @param  $metaData
     * @return string
     */
    private function getBodyContent($messageData, $metaData)
    {
        if(!ine($messageData, 'message') && !ine($metaData, 'message')) return '';

        if(ine($messageData, 'message')) {
            return $messageData['message'];
        }

        return $metaData['message'];
    }

    public function checkThreadIsVadileOrNot($threadId, $number)
    {
        $valideThreadId = MessageThread::where('id', $threadId)->where('phone_number', $number)->where('created_by', Auth::id())->first();

        if(!$valideThreadId) {
            throw new PhoneMessageException(trans('This Thread is invalid'));
        }

        return $valideThreadId['id'];
    }

    public function getParticipants($withoutCodeNumber)
    {
        $companyId = getScopeId();
        $customerId['customer'] = DB::table('phones')
                        ->join('customers', 'phones.customer_id', '=', 'customers.id')
                        ->where('customers.company_id', $companyId)
                        ->where('phones.number', $withoutCodeNumber)
                        ->pluck('customer_id')
                        ->toArray();

        $userId['user'] = DB::table('user_profile')
                        ->join('users', 'user_profile.user_id', '=', 'users.id')
                        ->where('users.company_id', $companyId)
                        ->where(function($query) use ($withoutCodeNumber){
                            $query->where('user_profile.phone', $withoutCodeNumber)
                            ->orWhere('user_profile.additional_phone', 'LIKE', '%' . $withoutCodeNumber . '%');
                        })->pluck('user_id')
                        ->toArray();

        array_push($userId['user'], Auth::id());
        $uniqueCustomer['customer'] = array_unique($customerId['customer']);
        $uniqueUser['user'] = array_unique($userId['user']);
        $participants = array_merge($uniqueCustomer, $uniqueUser);

        return $participants;
    }

    public function getUserIdOfTwilioNumber($userNumber)
    {
        $twilioNumber = TwilioNumber::where('phone_number', '=' , $userNumber)->first();
        if (!$twilioNumber) {
            return;
        }

        $userId = $twilioNumber->user_id;

        return $userId;
    }
}
