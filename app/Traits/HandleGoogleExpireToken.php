<?php

namespace App\Traits;

use App\Events\GoogleTokenExpired;
use App\Models\GoogleClient;
use Illuminate\Support\Facades\Event;

trait HandleGoogleExpireToken
{

    /**
     * Fire token expire token
     * @param  string $token | Google accesss token
     * @return void
     */
    public function fireTokenExpireEvent($token)
    {
        $googleClient = GoogleClient::whereToken($token)->first();

        if (!$googleClient) {
            return;
        }

        Event::fire(
            'JobProgress.GoogleCalender.Events.GoogleTokenExpired',
            new GoogleTokenExpired($googleClient)
        );
    }

    public function isTokenExpireException($e)
    {
        $exceptionType = class_basename($e);
        $exceptionCode = $e->getCode();

        if (strpos($e->getMessage(), 'invalid_grant') !== false) {
            return true;
        }

        return (in_array($exceptionType, [
                'Google_Auth_Exception',
                'Google_Service_Exception',
            ]) && in_array($exceptionCode, [401]));
    }
}
