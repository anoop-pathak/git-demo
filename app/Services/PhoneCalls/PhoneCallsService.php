<?php
namespace App\Services\Phonecalls;

use App\Repositories\PhoneCallsRepository;
use App\Services\Twilio\TwilioService;
use App\Exceptions\TwilioException;

class phoneCallsService
{
	private $client;

	public function __construct(TwilioService $twilioService, PhoneCallsRepository $repo)
	{
		$this->twilioService = $twilioService;
		$this->repo = $repo;

	}


	public function getFilteredCalls($filters) {

		return $this->repo->getFilteredCalls($filters);
	}

	public function getById($id) {

		return $this->repo->getById($id);
	}

	public function voiceCall($phoneNumber, $countryCode, $meta = array())
	{
		$fromNumber = config('mobile-message.from_address');
		$statusCallbackUrl = config('mobile-message.twilio_voice_response');
		$code = config('mobile-message.country_code.'.$countryCode);

		if(!$code) {
			throw new TwilioException("Invalid country code.");
		}

		$toNumber = $code.ltrim($phoneNumber, '0');

		$data = [
			'method' => 'GET',
            'statusCallback'       => $statusCallbackUrl,
            'statusCallbackMethod' => 'POST',
            'url' => 'http://demo.twilio.com/docs/voice.xml'
        ];

		$callData = $this->twilioService->voiceCall($toNumber, $fromNumber, $data);
		$callData = $callData->toArray();
		$call 	  = $this->repo->save($callData, $meta);
		return $call;
	}
}