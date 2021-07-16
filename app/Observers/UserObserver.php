<?php

namespace App\Observers;

use App\Models\User;
use Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class UserObserver
{

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.created: User', 'App\Observers\UserObserver@created');
        $event->listen('eloquent.updated: User', 'App\Observers\UserObserver@updated');
    }

    // On User Created..
    public function created($user)
    {

        $group = [User::GROUP_SUPERADMIN];

        if ($user->company_id && !in_array($user->group_id, $group)) {
            $count = User::whereCompanyId($user->company_id)
                ->whereNotIn('group_id', $group)
                ->withTrashed()
                ->count();

            if ($count) {
                $count -= 1;
            }

            $user->update(['color' => \config('colors.' . $count)]);
        }
    }

    public function updated($user)
    {
        return;
        $this->sendEmailUpdatedMail($user);
    }

    /********** Private Methods **********/
    /**
     * send email to user if his email updated by owner admin
     * @param  User     |$user  | object of user model
     * @return void
     */
    private function sendEmailUpdatedMail($user)
    {
        if($user->id == Auth::id()) return;
        $input = Request::all();
        if((!$user->multiple_account) || (!ine($input, 'email'))) return;
        $oldUser = $user->getOriginal();
        if($user->email == $oldUser['email']) return;
        $admin = Auth::user();
        $data = [
            'user'      => $user,
            'old_user'  => $oldUser,
            'admin'     => $admin,
            'company'   => $admin->company
        ];
        Mail::send('emails.users.email_changed_by_admin', $data, function($mail) use($oldUser) {
            $mail->to($oldUser['email']);
            $mail->subject(trans('response.events.email_subjects.email_changed_by_admin'));
        });
    }
}
