<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\User;
use App\Services\Users\AuthenticationService;
use Illuminate\Support\Facades\Config;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class RemindersController extends Controller
{

    protected $authService;


    /**
     * Class Constructor
     * @param    $authService
     */
    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Handle a POST request to remind a user of their password.
     *
     * @return Response
     */
    public function postRemind()
    {
        $input = Request::onlyLegacy('email', 'url');

        $validate = Validator::make($input, User::getForgotPassRule());
        if ($validate->fails()) {
            return ApiResponse::validation($validate);
        }

        Config::set('app.url', Request::get('url'));
        switch ($response = Password::remind(Request::onlyLegacy('email'), function ($message) {
            $message->subject(trans('Reset Password'));
        })) {
            case Password::INVALID_USER:
                return ApiResponse::errorNotFound(Lang::get($response));

            case Password::REMINDER_SENT:
                return ApiResponse::success([
                    'message' => Lang::get('response.success.forget_password_email_sent')
                ]);
        }
    }


    /**
     * Handle a POST request to reset a user's password.
     *
     * @return Response
     */
    public function postReset()
    {
        $credentials = Request::onlyLegacy(
            'password',
            'password_confirmation',
            'token',
            'email'
        );


        $response = Password::reset($credentials, function ($user, $password) {
            $user->password = $password;

            $user->save();

            if($user->multiple_account) {
				User::where('email', $user->email)
					->where('id', '<>', $user->id)
					->update(['password' => $user->password]);
			}

            $this->authService->logoutFromAllDevices($user->id);
        });
        switch ($response) {
            case Password::INVALID_PASSWORD:
            case Password::INVALID_TOKEN:
            case Password::INVALID_USER:
                return ApiResponse::errorNotFound(Lang::get($response));

            case Password::PASSWORD_RESET:
                return ApiResponse::success([
                    'message' => Lang::get('response.success.password_changed')
                ]);
        }
    }
}
