<?php

namespace App\Handlers\Events;

use App\Models\ActivityLog;
use ActivityLogs;
use Firebase;
use App\Services\Subscriptions\SubscriptionServices;
use App\Services\Zendesk\ZendeskService;
use App\Transformers\UsersTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\UserSignature;

class UserEventHandler
{

    protected $subscriptionServices;
    protected $zendeskService;

    public function __construct(SubscriptionServices $subscriptionServices, ZendeskService $zendeskService)
    {

        $this->subscriptionServices = $subscriptionServices;
        $this->zendeskService = $zendeskService;
    }

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('JobProgress.Users.Events.UserWasCreated', 'App\Handlers\Events\UserEventHandler@defaultHandler');
        $event->listen('JobProgress.Users.Events.UserWasCreated', 'App\Handlers\Events\UserEventHandler@checkCompanySubscription');
        $event->listen('JobProgress.Users.Events.UserWasCreated', 'App\Handlers\Events\UserEventHandler@shareCredentails');
        $event->listen('JobProgress.Users.Events.UserWasCreated', 'App\Handlers\Events\UserEventHandler@maintainActivityLog');
        $event->listen('JobProgress.Users.Events.UserWasCreated', 'App\Handlers\Events\UserEventHandler@createSupportAccount');
        $event->listen('JobProgress.Users.Events.UserUpdated', 'App\Handlers\Events\UserEventHandler@shareCredentails');
        $event->listen('JobProgress.Users.Events.UserUpdated', 'App\Handlers\Events\UserEventHandler@updateAnonymous');
        $event->listen('JobProgress.Users.Events.UserUpdated', 'App\Handlers\Events\UserEventHandler@updateSupportAccount');
        $event->listen('JobProgress.Users.Events.UserUpdated', 'App\Handlers\Events\UserEventHandler@firebaseUpdate');
        $event->listen('JobProgress.Users.Events.UserActivated', 'App\Handlers\Events\UserEventHandler@checkCompanySubscription');
        $event->listen('JobProgress.Users.Events.UserDeactivated', 'App\Handlers\Events\UserEventHandler@checkCompanySubscription');
        $event->listen('JobProgress.Users.Events.UserSaveSignature', 'App\Handlers\Events\UserEventHandler@userSaveSignature');
        $event->listen('JobProgress.Users.Events.UpdateAnonymousUser', 'App\Handlers\Events\UserEventHandler@updateAnonymous');
    }

    public function defaultHandler($event)
    {
    }

    /**
     * @todo Temperery handle Exception if Recurly Connetion
     * user active status will not changle before check company subcription
     * user will not active untill payment will not done successfully
     * user will ot get deactivated until subsription will not check successfully
     */
    public function checkCompanySubscription($event)
    {

        $user = $event->user;
        try {
            // check if user is not a free user..
            if (!$user->free) {
                $this->subscriptionServices->checkForNextUpdation($user->company);
            }
        } catch (\Exception $e) {
            if ($user->active) {
                $user->active = false;
            } else {
                $user->active = true;
            }
            $user->save();
            throw new \Exception($e->getMessage());
        }
    }

    public function shareCredentails($event)
    {
        $user = $event->user;
        if($user->multiple_account) return;

        $data = $event->userData;
        if (ine($data, 'send_mail') && ine($data, 'password')) {
            Mail::send('emails.users.share_credentials', $data, function ($message) use ($data) {
                $message->to($data['email'])->subject(\Lang::get('response.events.email_subjects.share_credentails'));
            });
        }
    }

    public function maintainActivityLog($event)
    {
        $user = $event->user;

        if ($user->company->isActive()) {
            $transform = App::make('Sorskod\Larasponse\Larasponse');
            $displayData = $transform->item($user, new UsersTransformer);
            $metaData['company'] = $user->company_id;

            // maintain activity log for superadmin..
            ActivityLogs::maintain(
                ActivityLog::FOR_SUPERADMIN,
                ActivityLog::USER_ADDED,
                $displayData,
                $metaData
            );
        }
    }

    public function createSupportAccount($event)
    {
        try {
            $user = $event->user;
            $company = $user->company;

            if (!is_null($company->zendesk_id)) {
                $service = App::make(ZendeskService::class);
                $zendeskUser = $this->zendeskService->addUser($user, $company->zendesk_id);

                $user->zendesk_id = $zendeskUser ? $zendeskUser->id : null;
                $user->save();
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function updateAnonymous($event)
    {
        $user = $event->user;
        if ($user->isOwner()) {
            $company = $user->company;
            $anonymous = $company->anonymous;
            if ($anonymous) {
                $anonymous->email = ucfirst(substr(clean($company->name), 0, 1)) . strtolower(clean($user->last_name)) . $company->id . '@jobprogress.com';
                $anonymous->password = 'JP' . strtolower(strrev(str_replace(' ', '', $user->last_name)));
                $anonymous->save();
            }
        }
    }

    public function updateSupportAccount($event)
    {
        try {
            $user = $event->user;
            $company = $user->company;
            if(!$company->zendesk_id && !$user->multiple_account) return false;

            if($user->multiple_account) {
                $user = $this->updateAllUserSupportAccount($user);
            }

            if(!$user->zendesk_id) {
                $zendeskUser = $this->zendeskService->addUser($user, $company->zendesk_id);
                $user->zendesk_id = $zendeskUser ? $zendeskUser->id : null;
                $user->save();
            }else {
                $zendeskUser = $this->zendeskService->updateUser($user);
            }

            return $zendeskUser;
        } catch (\Exception $e) {
            Log::error($e);
        }
    }

    public function firebaseUpdate($event)
    {
        $user = $event->user;
        $userData = $event->userData;
        if ($user->isCompanyUser()) {
            Firebase::updateUserSettings($user);
        }
    }

    public function userSaveSignature($event)
    {
        $user = $event->user;
        $userName = $user['first_name'] .' '. $user['last_name'];
        $userName = substr($userName, 0, 29);
        $im = imagecreate(410, 200);
        $size = imagesx($im) / strlen($userName) * 1.4;
        $textcolor = imagecolorallocate($im, 34, 34, 34);

        $white = imagecolorallocatealpha($im, 255, 255, 255, 127);
        imagefill($im, 0, 0, $white);
        $font = base_path().'/public/fonts/Satisfy-Regular.ttf';

        // determine the size of the text so we can center it
        $box = imagettfbbox($size, 0, $font, $userName);
        $text_width = abs($box[2]) - abs($box[0]);
        $text_height = abs($box[7]) - abs($box[1]);
        $image_width = imagesx($im);
        $image_height = imagesy($im);
        $x = ($image_width - $text_width) / 2;
        $y = ($image_height + $text_height) / 2;

        imagettftext($im, $size, 0, $x, $y, $textcolor, $font, $userName);

        header("Content-type: image/png");

        ob_start ();
        // // output and destroy image
        imagepng($im);

        $image_data = ob_get_contents();

        ob_end_clean();

        $base64_image = base64_encode($image_data);

        $image_data_base64 = 'data:image/png;base64,'. $base64_image;

        $userSignature = new UserSignature;
        $userSignature->user_id = $user['id'];
        $userSignature->signature = $image_data_base64;

        $userSignature->save();
    }


     /********** Private Functions **********/
     private function updateAllUserSupportAccount($user)
     {
         $users = User::with('company')
             ->where('email', $user->email)
             ->get();
         foreach ($users as $value) {
             if($user->id == $value->id) continue;

             $company = $value->company;

             if(!($company->zendesk_id)) continue;

             if(!$user->zendesk_id) {
                 $zendeskUser = $this->zendeskService->addUser($user, $company->zendesk_id);
                 $user->zendesk_id = $zendeskUser ? $zendeskUser->id : null;
                 $user->save();
             }else {
                 $zendeskUser = $this->zendeskService->updateUser($user);
             }
         }
         return $user;
     }
}
