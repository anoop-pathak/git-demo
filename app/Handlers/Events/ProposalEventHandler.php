<?php

namespace App\Handlers\Events;

use App\Models\Proposal;
use Illuminate\Support\Facades\Mail;
use Settings;
use MobileNotification;
use FlySystem;
use App\Models\User;

class ProposalEventHandler
{

    // here is the listener
    public function subscribe($event)
    {

        $event->listen('JobProgress.Workflow.Steps.Proposal.Events.ShareProposalStatus', 'App\Handlers\Events\ProposalEventHandler@proposalShare');
    }

    public function proposalShare($event)
    {
        $proposal = $event->proposal;
        $thankYouEmail = $event->thankYouEmail;

        if(!in_array($proposal->status, [Proposal::ACCEPTED, Proposal::REJECTED, Proposal::VIEWED])) goto END;
        if(!($company = $proposal->company)) goto END;
        if(!($company = $proposal->company)) goto END;

        if(!$proposal->token) {
            $token = generateUniqueToken();
            $proposal->token = $token;
            $proposal->save();
        }

        $notificationSetting = Settings::get('PROPOSAL_NOTIFICATION');
        $mobile = $notificationSetting['mobile'];
        $email  = $notificationSetting['email'];
        $proposalStatus = $proposal->status;
        if($proposalStatus == 'rejected') {
            $proposalStatus = 'reject';
        } elseif ($proposalStatus == 'accepted') {
            $proposalStatus = 'accept';
        }
         $sendMobileNotification = (isset($mobile[$proposalStatus]) && (isTrue($mobile[$proposalStatus])));
        $sendEmailNotification  = (isset($email[$proposalStatus]) && (isTrue($email[$proposalStatus])));
         if(!($sendEmailNotification || $sendMobileNotification)) goto END;

        // notify To
        $notifyTo = Settings::get('PROPOSAL_ACCEPTANCE_NOTIFICATION');

        $toOwner = (isset($notifyTo['owner']) && (isTrue($notifyTo['owner'])));

        $toAdmins = (isset($notifyTo['admins']) && (isTrue($notifyTo['admins'])));

        $toCustomerRep = (isset($notifyTo['customer_rep'])
            && (isTrue($notifyTo['customer_rep'])));

        $toEstimator = (isset($notifyTo['estimators']) && (isTrue($notifyTo['estimators'])));

        $toSender = (isset($notifyTo['sender']) && (isTrue($notifyTo['sender'])));

        $users = [];

        if ($toAdmins) {
            // get only acitve admin users
            $admins = $company->admins()->active()->get();

            if (sizeof($admins)) {
                $users = $admins->toArray();
            }
        }

        if ($toOwner) {
            $users[] = $company->subscriber->toArray();
        }

        $job = $proposal->job;
        $customer = $job->customer;

        if ($toCustomerRep && ($rep = $customer->rep()->active()->first())) {
            $users[] = $rep->toArray();
        }

        if ($toEstimator) {
            $estimators = $job->estimators()->active()->get();

            if (sizeof($estimators)) {
                $estimators = $estimators->toArray();
                $users = array_merge($users, $estimators);
            }
        }

        if ($toSender) {
            if ($sender = $proposal->sharedBy) {
                $users[] = $sender->toArray();
            }
        }

        if($proposal->isAccepted() && $customer->email && (bool) $thankYouEmail) {
            $data = [
                'company' => $company,
                'proposal' => $proposal
            ];
            Mail::send('emails.proposal-accept-customer-notification', $data, function($message) use ($customer)
            {
                $message->to($customer->email)->subject('Thank you for accepting our proposal');
            });
        }

        if (empty($users)) {
            goto END;
        }

        $users = uniqueMultidimArray($users);

        $attribute = $customer->full_name . ' / ' . $job->number;
        $url = \config('jp.site_job_url') . $customer->id . '/job/' . $job->id . '/overview';
        $link = '<a href=' . $url . '>' . $customer->full_name . ' / ' . $job->number . '</a>';

        $content = trans('response.events.email_contents.proposal_accepted', ['attribute' => $link]);
        $subject = trans('response.events.email_subjects.proposal_accepted', ['attribute' => $attribute]);

        $title = 'Proposal Accepted - '.$attribute;
        $message = 'Congratulations! Your proposal for '.$attribute. ' has been accepted.';
        $type = 'proposal_accepted';

        if ($proposal->status == Proposal::REJECTED) {
            $content = trans('response.events.email_contents.proposal_rejected', ['attribute' => $link]);
            $subject = trans('response.events.email_subjects.proposal_rejected', ['attribute' => $attribute]);
        } elseif($proposal->status == Proposal::VIEWED) {
            $content = trans('response.events.email_contents.proposal_viewed', ['attribute' => $link]);
            $subject = trans('response.events.email_subjects.proposal_viewed', ['attribute' => $attribute]);
            $title = 'Proposal Viewed - '.$attribute;
            $message = 'Your proposal for '.$attribute. ' has been viewed.';
        }

        $data = [
            'company' => $company,
            'content' => $content,
            'proposal' => $proposal
        ];

        //send email notification
        if($sendEmailNotification) {
            foreach ($users as $user) {
                $data['user'] = $user;
                Mail::send('emails.share-proposal-status', $data, function($message) use ($user, $subject) {
                    $message->to($user['email'])->subject($subject);
                });
            }
        }
         //send push  notification on proposal acceptance, rejected or viewed
        $jobMeta = $job->jobMeta->pluck('meta_value','meta_key')->toArray();
        $userIds = array_column($users, 'id');
        if($sendMobileNotification){
            $meta = [
                'job_id'            => $job->id,
                'customer_id'       => $customer->id,
                'proposal_id'       => $proposal->id,
                'stage_resource_id' => isset($job->getCurrentStage()['resource_id']) ? $job->getCurrentStage()['resource_id'] : null,
                'job_resource_id'   => isset($jobMeta['resource_id']) ? $jobMeta['resource_id'] : null,
                'company_id'        => $job->company_id,
            ];
            MobileNotification::send($userIds, $title, $type, $message, $meta);
        }

        END:
    }
}
