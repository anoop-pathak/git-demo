<?php
namespace App\Services\Twilio;

use Twilio\Rest\Client;
use Twilio\Exceptions\RestException;
use App\Exceptions\TwilioException;
use Exception;

class TwilioService
{
	private $client;

	public function __construct()
	{
	    $this->client = new Client(
	    	config('mobile-message.account_id'),
	    	config('mobile-message.token')
	    );
	}

	/**
	 * Send Queue message
	 * @param  int  $phoneNumber
	 * @param  array       $data Array
	 * @return Void
	 */
	public function sendMessage($phoneNumber, $data = [])
	{
		if(!$phoneNumber) return false;

		try {
			$messageData = $this->client->messages->create($phoneNumber, $data);
		} catch(RestException $e) {

			$this->twilioExceptionThrow($e);
		} catch(\Exception $e) {
			throw $e;
		}

		return $messageData;
	}

	public function voiceCall($toNumber, $fromNumber, $data = array())
	{
		if(!$fromNumber) return false;
		try {
			$callData = $this->client->calls->create($toNumber, $fromNumber, $data);
		} catch(RestException $e) {

			$this->twilioExceptionThrow($e);
		} catch(\Exception $e) {
			throw $e;
		}

		return $callData;

	}

	/**
	 * Handle Twilio Exception
	 * @param  Exception $e Exception
	 * @return error throw
	 */
	private function twilioExceptionThrow(Exception $e)
	{
		switch ($e->getCode()) {
			case 21210:
				throw new TwilioException("The source phone number provided, is not yet verified.");
				break;
			case 21211:
				throw new TwilioException("Invalid phone number.");
				break;
			case 21614:
				throw new TwilioException("This number is not a valid mobile number.");
				break;
			case 21617:
				throw new TwilioException("Sms text limit is not more than upto 1600 character");
				break;
			case 21608:
				throw new TwilioException("Please enter a verified number.");
				break;
			default:
				throw new TwilioException($e->getMessage());
				break;
		}
	}
}