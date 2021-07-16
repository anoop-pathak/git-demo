<?php

namespace App\Services\Emails;

use App\Models\Company;
use App\Models\Email;
use App\Models\User;
use FlySystem;
use Firebase;
use MobileNotification;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Settings;
use App\Models\Job;
use App\Models\Customer;

class EmailQueueHandler
{

    public function fire($job, $data)
    {
        $job->delete();

        try {
            $templatePath = ine($data, 'template') ? $data['template']  : 'emails.email';
            $currentUser = User::find($data['user_id']);
            $company = $currentUser->company;
            $email = Email::find($data['email_id']);
            Log::info('EmailQueueHandler Worker Start - SendEmail information '.$email->id);

			if($email->status == Email::EMAIL_SENT) {
				Log::info('EmailQueueHandler Worker End - SendEmail information '.$email->id);

				return $job->delete();
			}
            $customer = $emailJob = null;

            if(ine($data, 'job_id') && !is_array($data['job_id'])) {
				$emailJob = Job::withTrashed()->find($data['job_id']);
            }

			if(ine($data, 'customer_id')) {
				$customer = Customer::withTrashed()->find($data['customer_id']);
            }

            Mail::send($templatePath, [
                'content' => $email->content,
                'company' => $company,
                'website_link' => $data['website_link'],
                'email' => $email,
				'customer' => $customer,
				'current_user' => $currentUser,
				'job' => $emailJob
            ], function ($message) use ($data, $currentUser, $email) {
                $message->from(
                    config('mail.from.address'),
                    $currentUser->first_name . ' ' . $currentUser->last_name
                );

                $message->replyTo(
                    $data['reply_to'],
                    $currentUser->first_name . ' ' . $currentUser->last_name
                );

                $message->subject($email->subject);
                $to = arry_fu($data['to']);
                $message->to($to);
                if ($cc = arry_fu($data['cc'])) {
                    $message->cc($cc);
                }
                if ($bcc = arry_fu($data['bcc'])) {
                    $message->bcc($bcc);
                }

                foreach ($data['files'] as $file) {
                    $message->attachData(FlySystem::read($file['path']), $file['name']);
                }
            });

            // update status..
            $email->status = Email::EMAIL_SENT;
            $email->save();
            $job->delete();

            Log::info('EmailQueueHandler Worker End - SendEmail information '.$email->id);

            Log::info('worker - sendEmail information '.$email->id, [
				'Context' => getScopeId(),
				'Setting_User' => Settings::getUser(),
				'logged_in_user_id' => $currentUser->id,
				'logged_in_user_comany_id' => $currentUser->company_id,
				'CUSTOMER_REP_IN_BCC' => Settings::get('CUSTOMER_REP_IN_BCC'),
				'USER_BCC_ADDRESS' => Settings::get('USER_BCC_ADDRESS'),
				'DATA' => [
					'to'	  	=>	$data['to'],
	            	'cc'	  	=>	$data['cc'],
	            	'bcc'	  	=>	$data['bcc'],
	            	'email_id'	=> 	$email->id,
	            ],
			]);
        } catch (\Exception $e) {
            $this->sendNotificationMailDelievryFails($email, $e);

			$email->bounce_notification_response = $e->getMessage();
			$email->save();

            Log::error('Email Queue Handler error :' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine());

            // staus update
            if ($email) {
                // $email->status = Email::FAILED;
                // $email->save();
                // Log::warning('Email Id :' . $email->id);
            }
        }
    }

    private function sendNotificationMailDelievryFails($failedEmail, $e)
    {
        try {
            // set from address
            $from = 'Delivery-Failed@jobprogress.com';

            // set To address
            $to = [];
            if ($failedEmail->fromAddress) {
                $to[] = $failedEmail->fromAddress;
            } else {
                if (isset($failedEmail->createdBy->email)) {
                    $to[] = $failedEmail->createdBy->email;
                }
            }

            $meta = [
                'reply_to' => 1234743,
            ];

            $content = "<p>Your message wasn't delivered. Please try again. </p>
			<p>Note: Please ensure attachment size should be under 10MB.</p>
            <p>This is an automatically generated Delivery Status Notification. Please do not reply. Orignal Email Id: {$failedEmail->id} and created by Id is {$failedEmail->created_by} </p>";

            $emailRepo = App::make(\App\Repositories\EmailsRepository::class);

            $email = $emailRepo->save(
                $type = Email::RECEIVED,
                $subject = 'Re: ' . $failedEmail->subject,
                $content,
                $from,
                $to,
                $cc = [],
                $bcc = [],
                $attachments = [],
                24,
                $meta
            );

            $email->company_id = 12;
            $email->is_read = false;
            $email->status = Email::BOUNCED;
            $email->save();

            Log::error("sendNotificationMailDelievryFails got called");
			Log::error($e);

            // push notification and firbase count..
            /*if (isset($failedEmail->created_by)) {
                //set company scope
                $company = Company::find($failedEmail->company_id);
                Config::set('company_scope_id', $company->id);
                $context = App::make(\App\Services\Contexts\Context::class);
                $context->set($company);

                //update firebase unread count of user
                Firebase::updateUserEmailCount($failedEmail->created_by);
            // }

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
            $type = 'new_email';*/

            // MobileNotification::send($email->created_by, $title, $type, $message, $meta);
        } catch (\Exception $e) {
            Log::error($e);
        }
    }
}
