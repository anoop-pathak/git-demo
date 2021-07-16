<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Queue;
use Exception;
use Illuminate\Support\Facades\Log;

class QuickBookWebHookNotificationsController extends ApiController
{

	private $verifyToken = 'b88157a8-75ae-4949-bd3d-f4f3ae16f5cd';

	public function __construct()
	{
		parent::__construct();

		$this->verifyToken = config('jp.quickbook.verify_token');
	}

	/**
	 * Handle QuickBook webhook notifications
	 * @return $string
	 */
	public function handle()
	{

		try {
			// Read post data. we are reading in this way because sometimes if request payload is not valid json then
			// it will not covert to valid array by PHP, in order to get original request data input stream is the best way.
			$payLoad = file_get_contents("php://input");

			// In any case log all the data from QuickBook.
			// Log::info("QuickBook webhook payload: \n $payLoad \n", []);

			$headers = $this->validateRequest($payLoad);

			// Process all the notifications in the queue to make response faster less 3 seconds so that every thing works as
			// suggested by QuickBook webhooks specifications. Do not process the payload in the main thread.
			Queue::connection('qbo')->push('\App\Services\QuickBooks\TwoWaySync\QueueHandler\NotificationsHandler@handle', ['payload'=>$payLoad, 'headers'=> $headers]);

			return "Successful request";

		} catch (Exception $e) {

			return ($e);

			// Logging exception if any exception occurred
			Log::info("QuickBook notification exception: ", [$e->getMessage()]);

			return "Processing Error";
		}
	}

	private function validateRequest($payLoad)
	{
		$headers = Request::header();

		if( !$this->isValidJSON($payLoad) || !Request::isJson()) {
			throw new Exception('Invalid QuickBook notification payload.');
		};

		if ( !ine($headers, 'intuit-signature') || !ine($headers, 'intuit-t-id')) {
			throw new Exception('Verification headers missing.');
		}

		$payloadHash = hash_hmac('sha256', $payLoad, $this->verifyToken);

		$singatureHash = bin2hex(base64_decode(Request::header('intuit-signature')));

		if($payloadHash == $singatureHash) {
			return $headers;
		} else {
			throw new Exception('Verification of QuickBook notification payload failed.');
		}
	}


	function isValidJSON($string) {

		if (!isset($string) || trim($string) === '') {
			return false;
		}

		@json_decode($string);

		if (json_last_error() != JSON_ERROR_NONE) {
			return false;
		}

		return true;
	}
}
