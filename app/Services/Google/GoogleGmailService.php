<?php

namespace App\Services\Google;

use App\Exceptions\GoogleAccountNotConnectedException;
use App\Exceptions\NotFoundException;
use Google_Service_Gmail;
use Google_Service_Gmail_ModifyThreadRequest;
use Illuminate\Support\Facades\Auth;

class GoogleGmailService
{
    protected $client;
    protected $accessToken;

    public function __construct()
    {
        $this->client = new \Google_Client();
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setState('offline');
    }

    /**
     * get gmail thread list
     * @param $input
     * @return array of threads
     */
    public function getThreadList($input)
    {
        $data = [];

        $optParams = $this->makeQuery($input);

        $service = new Google_Service_Gmail($this->getClient());

        $threads = $service->users_threads->listUsersThreads('me', $optParams);

        foreach ($threads->getThreads() as $key => $thread) {
            $singleThread = $service->users_threads->get('me', $thread->getId());
            $messages = $singleThread->getMessages();
            $latestMessage = $messages[count($messages) - 1];

            $data[$key]['is_read'] = $this->isRead($messages);
            $data[$key]['thread_id'] = $thread->getId();
            $data[$key]['content'] = $this->mapData($latestMessage, false);
            $data[$key]['content']['content'] = $thread->getSnippet();
        }

        return [
            'data' => $data,
            'next_page_token' => $threads->getNextPageToken(),
        ];
    }

    /**
     * get gmail thread
     * @param  $threadId
     * @return $data
     */
    public function getSingleThread($threadId)
    {
        try {
            $data = [];

            $service = new Google_Service_Gmail($this->getClient());
            $thread = $service->users_threads->get('me', $threadId);
            $messages = $thread->getMessages();

            foreach ($messages as $key => $message) {
                $data[$key]['thread_id'] = $threadId;
                $data[$key]['content'] = $this->mapData($message);

                // mark as read
                if (in_array('UNREAD', $message->getLabelIds())) {
                    $this->markAsRead($threadId, true);
                }
            }

            return $data;
        } catch (\Exception $e) {
            if ($e->getCode() == 404) {
                throw new NotFoundException('Email does not exist.', 404);
            }
        }
    }

    /**
     * get attachment
     * @param  $messageId
     * @param  $attachmentId
     * @return $attachment
     */
    public function getAttachment($messageId, $attachmentId)
    {
        try {
            $service = new Google_Service_Gmail($this->getClient());

            $attachment = $service->users_messages_attachments->get('me', $messageId, $attachmentId);

            return $attachment;
        } catch (\Exception $e) {
            if ($e->getCode() == 400) {
                throw new NotFoundException('Attachment not found.', 404);
            }
        }
    }

    /**
     * mark as read
     * @param  $threadId
     * @return $thread
     */
    public function markAsRead($threadId, $read = true)
    {
        $service = new Google_Service_Gmail($this->getClient());
        $mods = new Google_Service_Gmail_ModifyThreadRequest();

        if ($read) {
            $mods->setRemoveLabelIds(["UNREAD"]);
        } else {
            $mods->setAddLabelIds(["UNREAD"]);
        }

        $thread = $service->users_threads->modify('me', $threadId, $mods);

        return $thread;
    }

    // /**
    //  * trash gmail thread
    //  * @param  $threadId
    //  * @return $thread
    //  */
    // public function trashThread($threadId, $trash = true)
    // {

    // 	$service = new Google_Service_Gmail($this->client);

    // 	if ($trash) {
    // 		$thread = $service->users_threads->trash($this->userEmail, $threadId);
    // 	} else {
    // 		$thread = $service->users_threads->untrash($this->userEmail, $threadId);
    // 	}

    // 	return $thread;
    // }


    /**************** Private Section ********************/

    /**
     * Get Google Client Object
     * @return object
     */
    private function getClient()
    {
        $user = \Auth::user();
        $googleClient = $user->googleClient;

        if (!$googleClient || !($googleClient->token)) {
            throw new GoogleAccountNotConnectedException(trans('response.error.google_account_not_connected'));
        }

        $this->accessToken = $googleClient->token;

        $this->client->setAccessToken($this->accessToken);

        return $this->client;
    }

    /**
     * map messages data
     * @param  $message
     * @param  boolean $includeMessage
     * @return array $ret
     */
    private function mapData($message, $includeMessage = true)
    {
        $ret = [];
        $ret['message_id'] = $message->getId();

        $payload = $message->getPayload();
        $headers = $payload->getHeaders();

        // get headers
        foreach ($headers as $key => $header) {
            if ($header->getName() == 'Subject') {
                $ret['subject'] = $header->getValue();
            }
            if ($header->getName() == 'To') {
                $ret['to'] = $header->getValue();
            }
            if ($header->getName() == 'From') {
                $ret['from'] = $header->getValue();
            }
            if ($header->getName() == 'Cc') {
                $ret['cc'] = $header->getValue();
            }
            if ($header->getName() == 'Date') {
                $ret['date'] = $header->getValue();
            }
            if ($header->getName() == 'Reply-To') {
                $ret['reply_to'] = $header->getValue();
            }
        }

        if (!$includeMessage) {
            return $ret;
        }

        $bodyContent = $payload->getBody()->getData();

        // get message content
        if (empty($bodyContent)) {
            foreach ($payload->getParts() as $key => $part) {
                if ($part->getMimeType() == 'text/html') {
                    $bodyContent = $part->getBody()->getData();
                }

                if ($part->getFileName()) {
                    $ret['attachment'][$key]['mime_type'] = $part->getMimeType();
                    $ret['attachment'][$key]['name'] = $part->getFileName();
                    $ret['attachment'][$key]['size'] = $part->getbody()->getSize();
                    $ret['attachment'][$key]['id'] = $part->getbody()->getAttachmentId();
                    $ret['attachment'][$key]['size'] = $part->getbody()->getSize();
                }

                if (empty($bodyContent)) {
                    foreach ($part->getParts() as $key => $value) {
                        if ($value->getMimeType() == 'text/html') {
                            $bodyContent = $value->getBody()->getData();
                        }

                        if (empty($bodyContent)) {
                            foreach ($value->getParts() as $key => $singlePart) {
                                if ($singlePart->getMimeType() == 'text/html') {
                                    $bodyContent = $singlePart->getBody()->getData();
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }

        $ret['content'] = base64_decode(str_pad(strtr($bodyContent, '-_', '+/'), strlen($bodyContent) % 4));

        return $ret;
    }

    /**
     * make query for thread list
     * @param  $filters
     * @return $optParams
     */
    private function makeQuery($filters)
    {
        $optParams = [];

        $optParams['pageToken'] = $filters['next_page_token'];
        $optParams['maxResults'] = 10;
        $optParams['labelIds'] = 'INBOX';

        if (ine($filters, 'subject')) {
            $optParams['q'] = "subject: '{$filters['subject']}'";
        }

        // set limit..
        // if(ine($filters, 'limit')) {
        // 	$optParams['maxResults'] = $filters['limit'];
        // }

        // if (ine($filters, 'label')) {
        // 	$optParams['q'] = "in:('{$filters['label']}')";
        // }

        // 	// if ($filters['label'] == 'starred' || $filters['label'] == 'important') {
        // 	// 	$optParams['q'] = 'is:('.$filters['label'].')';
        // 	// } else {
        // 	// }

        return $optParams;
    }

    /**
     * check status of a message
     * @param  $messages
     * @return boolean $isRead
     */
    private function isRead($messages)
    {
        $isRead = true;

        foreach ($messages as $key => $message) {
            if (in_array('UNREAD', (array)$message->getLabelIds())) {
                $isRead = false;
                break;
            }
        }

        return $isRead;
    }

// 	public function getMessageBody($payload, $rec = false)
// 	{
// 		// dd($payload);
// 		$ret = [];

// 		if($parts = $payload->getParts()) {
// 			foreach ($parts as $key => $part) {

// 				if($part->getMimeType() == 'text/html') {
// 					$ret['content'] = $part->getBody()->getData();
// 				}

// 				if ($part->getFileName()) {
// 					$ret['attachment'][$key]['mime_type'] = $part->getMimeType();
// 					$ret['attachment'][$key]['name'] = $part->getFileName();
// 					$ret['attachment'][$key]['id'] = $part->getbody()->getAttachmentId();
// 				}

// 				if($part->getParts()) {
// 					return $this->getMessageBody($part, true);
// 				}
// 			}
// 			return $ret;
// 		}else {
// 			$ret['content'] = $payload->getBody()->getData();
// 		}

// // dd($ret);
// 		return $ret;
// 	}
}
