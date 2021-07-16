<?php

namespace App\Services\SubContractors;

use App\Exceptions\EmailAlreadyExistsExceptions;
use App\Models\User;
use App\Services\Contexts\Context;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use App\Repositories\UserRepository;

class SubContractorService
{
    protected $scope;

    public function __construct(Context $scope, UserRepository $userRepo)
    {
        $this->scope = $scope;
        $this->userRepo = $userRepo;
    }


    public function setSubContractorPassword($input)
    {
        $user = User::whereId($input['sub_id'])
            ->whereCompanyId($this->scope->id())
            ->first();

        if (!$user) {
            throw new ModelNotFoundException("User not found.");
        }

        $oldUser = clone $user;

        $existingEmail = User::whereEmail($input['email'])
            ->where('email', '<>', $user->email)
            ->exists();

        if ($existingEmail) {
            throw new EmailAlreadyExistsExceptions("Email is already exists.");
        }

        $subContractor = User::whereId($input['sub_id'])
            ->onlySubContractors()
            ->first();

        if (!$subContractor) {
            throw new ModelNotFoundException("Sub contractor not found.");
        }

        $subContractor->update([
            'email' => $input['email'],
            'password' => $input['password']
        ]);

        if($subContractor->multiple_account) {
            $this->userRepo->updateAllUserAccounts($subContractor, $oldUser->email);
		}

        $input['first_name'] = $subContractor->first_name;
        $input['last_name'] = $subContractor->last_name;

        // send credentials detail
        if ($input['send_mail']) {
            $this->sendMail($input);
        }

        return $subContractor;
    }

    /**
     * send mail to user with credentials
     * @param  $input
     */
    private function sendMail($input)
    {
        if (ine($input, 'send_mail') && ine($input, 'password')) {
            Mail::send('emails.users.share_credentials', $input, function ($message) use ($input) {
                $message->to($input['email'])->subject(\Lang::get('response.events.email_subjects.share_credentails'));
            });
        }
    }
}
