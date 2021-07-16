<?php

namespace App\Services\Emails;

use App\Models\Email;
use App\Repositories\EmailsRepository;
use Firebase;
use MobileNotification;
use Illuminate\Support\Facades\Log;
use Request;
use Illuminate\Support\Facades\DB;
use App\Models\EmailRecipient;

class EmailBounceHandler
{

    public function __construct(EmailsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function handleSNSNotifications()
    {
        $input = json_decode(Request::getContent(), true);

        if (!isset($input['TopicArn']) || !isset($input['SigningCertURL'])) {
            Log::warning("SNS ERROR: TopicArn or SigningCertURL not get. Invalid Notification");

            return;
        }

        // Log::info($input);

        //Should you need to check that your messages are coming from the correct topicArn
        $allowedTopic = config('mail.sns.arn-topic');
        if ($allowedTopic != $input['TopicArn']) {
            Log::warning("SNS ERROR: Allowed Topic ARN: " . $allowedTopic . " DOES NOT MATCH Calling Topic ARN: " . $input['TopicArn']);

            return;
        }

        //For security you can (should) validate the certificate, this does add an additional time demand on the system.
        $sourceDomain = config('mail.sns.source-domain');
        //Check For Certificate Source
        $domain = $this->getDomainFromUrl($input['SigningCertURL']);
        if ($domain != $sourceDomain) {
            Log::warning("SNS Key domain: " . $domain . " is not equal to allowed source domain:" . $sourceDomain);
            return;
        }

        if ($input['Type'] == 'SubscriptionConfirmation') {
            $client = new \GuzzleHttp\Client;
            $client->get($input['SubscribeURL']);

            return true;
        }

        $this->logBounceMessage($input);

        if ($input['Type'] == 'Notification') {
            $message = json_decode($input['Message'], true);

            // Log::info($message);
            switch ($message['notificationType']) {
                case 'Bounce':
                    $this->handleAndSaveBounceMail($message);
                    break;
                case 'Complaint':
                    //  Do Nothing
                    break;
                case 'Delivery':
                    $this->handleDeliveryMail($message);
                    break;
                default:
                    //  Do Nothing
                    break;
            }
        }

        return true;
    }

    /************** Private Section *************/
    /**
     * Handle And Save Bounce Mail
     * @param  array $message | Notification message data
     * @return [type]          [description]
     */
    private function handleAndSaveBounceMail(array $message)
    {
        $this->saveEmailBounceSummary($message);

        if (!isset($message['mail']['headers'])
            || !isset($message['bounce']['bouncedRecipients'])) {
            return;
        }

        $mailHeaders = $message['mail']['headers'];
        $replyTo = array_filter(array_map(function ($header) {
            if ($header['name'] == 'Reply-To') {
                return $header['value'];
            }
        }, $mailHeaders));

        $replyTo = reset($replyTo); // get first element from array..

        $prefix = "+" . config('mail.imap.reply-to.prefix');

        if (!strpos($replyTo, $prefix)) {
            return; // check if prefix match othervice return.
        }

        $replyToDomain = '@'.config('mail.imap.reply-to.domain');
        $replyToId = (int)explode($prefix, explode($replyToDomain, $replyTo)[0])[1];

        if (empty($replyToId)) {
            return;
        }

        // check if bounce mail exists
        if (!$bounceMail = Email::find($replyToId)) {
            return;
        }

        //get company id of reply to mail
        $companyScope = $bounceMail->company_id;
        setScopeId($companyScope);

        // get bounce reason
        $bounceType = $message['bounce']['bounceType'];
        $bounceSubType = $message['bounce']['bounceSubType'];
        $bounceReason = trans('email-content.bounce.' . $bounceType . '_' . $bounceSubType);

        foreach ($message['bounce']['bouncedRecipients'] as $bouncedRecipient) {
            // set To address
            $to = [];
            if ($bounceMail->fromAddress) {
                $to[] = $bounceMail->fromAddress;
            } else {
                if (isset($bounceMail->createdBy->email)) {
                    $to[] = $bounceMail->createdBy->email;
                }
            }

            // set from address
            $from = 'Delivery-Failed@jobprogress.com';

            $meta = [
                'reply_to' => $bounceMail->id,
            ];

            $content = "<p>Your message wasn't delivered to the recipient <b>" . $bouncedRecipient['emailAddress'] . "</b></p>";
            $bounceStatus = '';

			if(isset($bouncedRecipient['status'])) {
				$bounceStatus = $bouncedRecipient['status'];
			}

			if($bounceStatus == '5.7.1') {
                $content .= "<p>Note: Message not delivered. It has been rejected by recipient's email service provider.</p>";
            } else {
                $content .= "<p>Note: {$bounceReason}</p>";
            }

            $content .= "<p>This is an automatically generated Delivery Status Notification. Please do not reply.</p>";

            if(isset($bouncedRecipient['diagnosticCode'])) {
				$diagnosticCode = $bouncedRecipient['diagnosticCode'];
				$content .= "<br><br><p style='font-family:monospace'>$bounceStatus $diagnosticCode</p>";
			}

            $email = $this->repo->save(
                $type = Email::RECEIVED,
                $subject = 'Re: ' . $bounceMail->subject,
                $content,
                $from,
                $to,
                $cc = [],
                $bcc = [],
                $attachments = [],
                $bounceMail->created_by,
                $meta
            );
            $email->company_id = $companyScope;
            $email->is_read = false;
            $email->status = Email::BOUNCED;
            $email->bounce_notification_response = Request::getContent();
            $email->save();

            $recipient = EmailRecipient::where('email_id', $replyToId)
						->where('email', $bouncedRecipient['emailAddress'])
						->first();
			if(!$recipient) continue;
		 	$recipient->bounce_date_time = $message['bounce']['timestamp'];
		 	$recipient->save();

            $this->sendNotification($email);
        }

        return true;
    }

    /**
     * It takes a URL String and returns the domain portion only
     * @param  string $urlString | SigningCertURL
     * @return string
     */
    private function getDomainFromUrl($urlString)
    {
        $domain = "";
        $urlArray = parse_url($urlString);

        if ($urlArray == false) {
            $domain = "ERROR";
        } else {
            $domain = $urlArray['host'];
        }

        return $domain;
    }

    private function logBounceMessage($input)
    {
        try {
            Log::warning('BounceEmailBody: ' . json_encode($input));
        } catch (\Exception $e) {
            // nothing to do..
        }
    }

    private function sendNotification($email)
    {
        try {
            if (isset($email->created_by)) {
                //update firebase unread count of user
                Firebase::updateUserEmailCount($email->created_by);
            }

            $from = 'Delivery-Failed@jobprogress.com';

            $title = 'Email delivery failed';

            $message = [];
            if ($email->subject) {
                $message[] = $email->subject;
            }

            if ($body = strip_tags($email->content)) {
                $message[] = substr($body, 0, 100);
            }

            $message = implode(' - ', $message);

            $meta = [
                'thread_id' => $email->conversation_id,
                'email' => $email->from,
                'company_id'	=> $email->company_id,
            ];
            $type = 'new_email';

            MobileNotification::send($email->created_by, $title, $type, $message, $meta);
        } catch (\Exception $e) {
        }
    }

    private function saveEmailBounceSummary($message)
	{
		$bounce = $message['bounce'];
		$data = [];
		foreach ($bounce['bouncedRecipients'] as $bouncedRecipient) {
		 	$data[] = [
		 		'type'   => $bounce['bounceType'],
		 		'sub_type'   => $bounce['bounceSubType'],
		 		'email_address' => $bouncedRecipient['emailAddress'],
		 		'status' => $bouncedRecipient['status'],
		 		'reason' => $bouncedRecipient['diagnosticCode'],
		 		'created_at' => $bounce['timestamp'],
		 		'updated_at' => $bounce['timestamp'],
		 	];
         }

		 if(!empty($data)) {
		 	DB::Table('email_bounce_summary')->insert($data);
		 }
    }}

	private function handleDeliveryMail($message)
	{
		$mailHeaders = $message['mail']['headers'];
		$replyTo = array_filter(array_map(function($header) {
            if($header['name'] == 'Reply-To') return $header['value'];
	    }, $mailHeaders));

		$replyTo = reset($replyTo); // get first element from array..
	    $prefix = "+".config('mail.imap.reply-to.prefix');
	    if(!strpos($replyTo, $prefix)) return; // check if prefix match othervice return.

		$replyToDomain = '@'.config('mail.imap.reply-to.domain');
		$replyToId = (int)explode($prefix, explode($replyToDomain, $replyTo)[0])[1];

		if(empty($replyToId)) return;
		foreach ($message['delivery']['recipients'] as $recipient) {
			$recipient = EmailRecipient::where('email_id', $replyToId)->where('email', $recipient)->first();
			if(!$recipient) continue;
			$recipient->delivery_date_time = $message['delivery']['timestamp'];
		 	$recipient->save();
		}
	}
}
