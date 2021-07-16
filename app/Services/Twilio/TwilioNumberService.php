<?php
namespace App\Services\Twilio;

use Twilio\Rest\Client;
use App\Repositories\TwilioNumberRepository;
use Illuminate\Support\Collection;
use Exception;
use App\Exceptions\TwilioException;
use Illuminate\Support\Facades\Auth;

class TwilioNumberService
{
	private $client;

	public function __construct(TwilioNumberRepository $twilioNumberRepo)
	{
		$this->twilioNumberRepo = $twilioNumberRepo;

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
	public function getFromNumber()
	{
		$user = Auth::user();
		$phoneNumber = $this->twilioNumberRepo->getNumber($user->id);

		if(!$phoneNumber) {
			$phoneNumber = $this->getTwilioPhoneNumber($user);
		}
		return $phoneNumber;
	}

	/**
	 * For Purchasing new twilio number
	 * @param  int  $phoneNumber
	 * @param  array       $data Array
	 * @return Void
	 */

	public function getTwilioPhoneNumber($user)
	{
		$profile = $user->profile;
		$fullAddress = $profile->present()->fullAddressOneLine(true);
		if(!empty(trim($fullAddress))) {
			$location = geocode($fullAddress);
		}

		$latitude  = $location['lat'];
		$longitude = $location['lng'];

		// Set Country Code
		$country = $user->profile ? $user->profile->country : null;
		$countryCode = null;
		if($country) {
			$countryCode = $country->code;
		}

		// Set State Code
		$state = $user->profile ? $user->profile->state : null;
		$stateCode = $state ? $state->code : null;

		$zipCode = $user->profile ? $user->profile->zip : null;

		if (($countryCode != 'US' && $countryCode != 'CA') || $stateCode == 'WDC') {
			return $phoneNumber =  false;
		}

		// validate StateCode and CountryCode and ZipCode

		if(!$countryCode || !$stateCode || $zipCode == null) {
			throw new TwilioException(trans('response.error.allocate_twilio_number'));
		}

		$meta = [
			'country_code' => $countryCode,
			'latitude'   => $latitude,
			'longitude'  => $longitude,
			'zip_code'   => $zipCode,
			'state_code' => $stateCode
		];

		$phoneNumbers = $this->requestTwilioNumber($meta);

		// Buy/Allocate Phone number on twilio and save in DB
		try {
			$data = array(
				"phoneNumber" => $phoneNumbers['number'],
				"smsUrl" => config('mobile-message.sms_url'),
				"smsMethod" => config('mobile-message.sms_method')
			);
			$incomingPhoneNumber = $this->client->incomingPhoneNumbers->create($data);

			return $this->twilioNumberRepo->saveNumberInDB($incomingPhoneNumber, $phoneNumbers);
		} catch (Exception $e) {
			return $this->twilioExceptionThrow($e);
		}
	}

	/**
	 * Request twilio for phone number on the basis of zip code and state code and latitude and longitude.
	 *
	 * @param String $countryCode: country code
	 * @param String $stateCode
	 * @param String $zipCode
	 * @param String $latitude
	 * @param String $longitude
	 * @return Array
	 */
	private function requestTwilioNumber($meta)
	{
		$phoneNumbers = [];
		$responseData = [];
		$extraParam   = [];
		$response     = [];
		$readRequestParams = array(
			'smsEnabled' => true,
			'voiceEnabled' => true,
			'mmsEnabled' => true
		);

		if(ine($meta, 'latitude') && ine($meta, 'longitude')){
			$extraParam = [
				'nearLatLong' => $meta['latitude'].','.$meta['longitude'],
				'distance' => config('mobile-message.distance')
			];
			$fullRequestParams = array_merge($readRequestParams, $extraParam);
			$response = [
				'lat' =>  $meta['latitude'],
				'long' => $meta['longitude']
			];
			$fullResponse = array_merge($responseData, $response);
			$phoneNumbers = $this->client->availablePhoneNumbers($meta['country_code'])
				->local->read($fullRequestParams);
		}

		if(!$phoneNumbers && ine($meta, 'zip_code')){
			$extraParam = [
				'inPostalCode' => $meta['zip_code']
			];
			$fullRequestParams = array_merge($readRequestParams, $extraParam);
			$response = [
				'zip_code' =>  $meta['zip_code']
			];
			$fullResponse = array_merge($responseData, $response);
			$phoneNumbers = $this->client->availablePhoneNumbers($meta['country_code'])
				->local->read($fullRequestParams);
		}

		if(!$phoneNumbers && ine($meta, 'state_code')){
			$extraParam = [
				'inRegion' => $meta['state_code']
			];
			$fullRequestParams = array_merge($readRequestParams, $extraParam);
			$response = [
				'state_code' =>  $meta['state_code']
			];
			$fullResponse = array_merge($responseData, $response);
			$phoneNumbers = $this->client->availablePhoneNumbers($meta['country_code'])
				->local->read($fullRequestParams);
		}

		// Get First element after converting array into collection
		$collection = new Collection($phoneNumbers);
		$phoneNumber = [];
		if ($collection->first() != null) {
			$phoneNumber = $collection->first()->toArray();
		}

		if(!$phoneNumber) {
			throw new TwilioException(trans('response.error.number_not_found'));
		}

		$fullResponse['number'] = $phoneNumber['phoneNumber'];

		return $fullResponse;
	}

	/**
	 * Release Twilio Number
	 * @param  $twilioInstance
	 * @return response
	 */
	public function releaseTwilioNumber($twilioInstance)
	{
		try {
			$result = $this->client->incomingPhoneNumbers($twilioInstance->sid)->delete();
			return $result;
		} catch (Exception $e) {
			return $this->twilioExceptionThrow($e);
		}
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
			case 21608:
				throw new TwilioException("Please enter a verified number.");
				break;
			case 21404:
				throw new TwilioException("Trial accounts are allowed only one Twilio number. To purchase additional numbers, please upgrade your account.");
				break;
			default:
				throw new TwilioException($e->getMessage());
				break;
		}
		return true;
	}
}