<?php

namespace App\Handlers\Events;

use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Request;

class LogsEventHandler
{
    public function handle($level, $exception, $context)
    {
        if (App::environment('production') && $level == 'error') {
            try {
                $user = \Auth::user();
                $error = getErrorDetail($exception);
                $data = ['exception' => $error];
                $data['input'] = Request::all();
                $data['client'] = config('is_mobile') ? 'Mobile' : 'Web';
                $data['request_method'] = Request::method();
                $data['username'] = isset($user) ? $user->email : '';
                $data['subscription'] = isset($user) ? $user->company_id : '';
                $data['scope'] = getScopeId();
                $data['ips'] = json_encode(Request::ips());
                $data['request_path'] = Request::path();
                $data['platform'] = Request::header('platform');
                $data['app_version'] = Request::header('app-version');
                if (isset($data['input']['password'])) {
                    $data['input']['password'] = '*****';
                }

                if (isset($data['input']['password_confirmation'])) {
                    $data['input']['password_confirmation'] = '*****';
                }

                Mail::send('emails.error', $data, function ($message) {
                    // $message->from($email_app);
                    $message->to(config('jp.error_log_mail.to'));

                    $message->cc(config('jp.error_log_mail.cc'));

                    $message->subject('JP - An error logged');
                });
            } catch (\Exception $e) {
                // Log mail error..
                Log::warning('Error log mail sending fail.');
                Log::warning($e);
            }
        }
    }
}
