<?php

namespace App\Services\MobileMessages;

use App\Exceptions\MobileMessageException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Queue;
use Twilio\Rest\Client;

class MobileMessageService
{
    private $client;

    public function __construct()
    {
        $this->client = new Client(
            config('mobile-message.account_id'),
            config('mobile-message.token')
        );
    }

    public function send($phoneNumber, $message, $countryCode, $mediaUrls = [], $useQueue = false)
    {
        if (App::environment('local')) {
            return false;
        }

        $code = config('mobile-message.country_code.' . $countryCode);

        if (!$code) {
            return false;
        }

        $number = $code . ltrim($phoneNumber, '0');

        $data = [
            'phoneNumber' => $number,
            'message' => $message,
            'mediaUrls' => $mediaUrls,
        ];
        if ($useQueue) {
            Queue::push('App\Services\MobileMessages\MobileMessageService@sendMessage', $data);
        } else {
            $this->sendMobileMessage($data);
        }
    }

    /**
     * Send Queue message
     * @param  QueueObject $job QueueObject
     * @param  array $data Array
     * @return Void
     */
    public function sendMessage($job, $data = [])
    {
        $bodyData = [
            'from' => config('mobile-message.from_address'),
            'body' => $data['message'],
        ];

        if (!empty($urls = array_filter((array)$data['mediaUrls']))) {
            $bodyData['mediaUrl'] = $urls;
        }

        try {
            $this->client->messages->create($data['phoneNumber'], $bodyData);
        } catch (\Twilio\Exceptions\RestException $e) {
            switch ($e->getCode()) {
                case 21211:
                    Log::notice("Invalid phone number " . $data['phoneNumber']);
                    break;
                case 21614:
                    Log::notice($data['phoneNumber'] . " This number is not a valid mobile number.");
                    break;
                default:
                    Log::error('Mobile SMS Service: Error Code:' . $e->getCode() . ' Message:' . $e->getMessage() . ' Mobile Number:' . $data['phoneNumber']);
                    break;
            }
        } catch (\Exception $e) {
            $message = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::error('Mobile SMS Service: ' . $message);
        }
        $job->delete();
    }

    /********************PRIVATE METHOD*************/
    private function sendMobileMessage($data)
    {
        $bodyData = [
            'from' => config('mobile-message.from_address'),
            'body' => $data['message'],
        ];

        if (!empty($urls = array_filter((array)$data['mediaUrls']))) {
            $bodyData['mediaUrl'] = $urls;
        }

        try {
            $this->client->messages->create($data['phoneNumber'], $bodyData);
        } catch (\Twilio\Exceptions\RestException $e) {
            switch ($e->getCode()) {
                case 21211:
                    throw new MobileMessageException("Invalid phone number.");
                    break;
                case 21614:
                    throw new MobileMessageException("This number is not a valid mobile number.");
                    break;
            }
            throw new MobileMessageException($e->getMessage());
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
