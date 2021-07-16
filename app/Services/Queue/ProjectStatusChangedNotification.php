<?php

namespace App\Services\Queue;

use App\Exceptions\MobileMessageException;
use App\Models\Job;
use App\Models\ProjectStatusManager;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\MobileMessages\MobileMessageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProjectStatusChangedNotification
{
    /**
     * Notification
     * @param  Object $job Queue Object
     * @param  Array $data Array data
     * @return Void
     */
    public function fire($queueJob, $data)
    {
        try {
            if (!(ine($data, 'text_notification') || ine($data, 'email_notification'))) {
                return $queueJob->delete();
            }

            $sender = User::find($data['sender_id']);
            if (!$sender) {
                return $queueJob->delete();
            }

            $project = Job::find($data['project_id']);
            if (!$project) {
                return $queueJob->delete();
            }

            $company = $project->company;
            if (!$company) {
                return $queueJob->delete();
            }

            $customer = $project->customer;
            if (!$customer) {
                return $queueJob->delete();
            }

            $notifyUsers = User::whereIn('id', (array)$data['notify_users'])
                ->where('company_id', $project->company_id)
                ->active()
                ->get();
            if (!$notifyUsers->count()) {
                return $queueJob->delete();
            }

            $trades = $project->trades->pluck('name')->toArray();
            $workTypes = $project->workTypes->pluck('name')->toArray();
            $desc = implode(', ', $trades);
            if (!empty($workTypes)) {
                $desc .= ' / ' . implode(', ', $workTypes);
            }

            $oldStatus = ProjectStatusManager::find($data['old_status_id']);
            $status = ProjectStatusManager::find($project->status);
            if (!$oldStatus && $status) {
                $content = "The status for the project {$customer->full_name} / {$project->number} ({$desc}) was changed to {$status->name}";
            } elseif ($oldStatus && $status) {
                $content = "The status for the project {$customer->full_name} / {$project->number} ({$desc}) was changed from {$oldStatus->name} to {$status->name}";
            } else {
                return $job->delete();
            }

            if (ine($data, 'email_notification')) {
                $this->sendEmailNotification($company, $notifyUsers, $sender, $content);
            }

            if (ine($data, 'text_notification')) {
                $this->sendTextMessage($company, $notifyUsers, $content);
            }
        } catch (\Exception $e) {
            Log::info('Project Status Changed Notification: ' . getErrorDetail($e));
        }

        $queueJob->delete();
    }

    //send email notification
    private function sendEmailNotification($company, $notifyUsers, $sender, $content)
    {
        try {
            $subject = "Project Status Updated";
            foreach ($notifyUsers as $notifyUser) {
                Mail::send('emails.company-email', [
                    'content' => $content,
                    'company' => $company,
                    'user' => $notifyUser
                ], function ($message) use ($subject, $sender, $notifyUser) {
                    $fullName = $sender->first_name . ' ' . $sender->last_name;
                    $message->from(config('mail.from.address'), $fullName);
                    $message->to($notifyUser->email);
                    $message->subject($subject);
                });
            }
        } catch (\Exception $e) {
            Log::info('Project Status Changed Email Notification: ' . getErrorDetail($e));
        }
    }

    //send text message
    private function sendTextMessage($company, $notifyUsers, $content)
    {
        $userIds = $notifyUsers->pluck('id')->toArray();
        $profiles = UserProfile::whereIn('user_id', $userIds)->select('additional_phone')->get();
        $phones = [];

        foreach ($profiles as $profile) {
            $additionalPhones = $profile->additional_phone;

            foreach ((array)$additionalPhones as $additionalPhone) {
                if ($additionalPhone->label != 'cell') {
                    continue;
                }
                $phones[] = $additionalPhone->phone;
                break;
            }
        }

        if (!empty($phones)) {
            $countryCode = $company->country->code;
            foreach ($phones as $phone) {
                $this->sendMessage($phone, $content, $countryCode);
            }
        }
    }

    //send message
    private function sendMessage($phone, $message, $countryCode)
    {
        try {
            $sms = new  MobileMessageService;
            $sms->send($phone, $message, $countryCode);
        } catch (MobileMessageException $e) {
            Log::info('Project Status Changed Mobile Message Code:' . $e->getCode() . ' Message:' . $e->getMessage() . ' Mobile Number:' . $phone);
        } catch (\Exception $e) {
            Log::info('Project Status Changed Mobile Message Exception Code:' . $e->getCode() . ' Message:' . $e->getMessage() . ' Mobile Number:' . $phone);
        }
    }
}
