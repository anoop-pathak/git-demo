<?php

namespace App\Services\QuickBookPayments\Clients;

use App\Services\QuickBookPayments\Actions\AccessToken as AccessTokenActions;
use App\Services\QuickBookPayments\Actions\Charges as ChargesActions;
use App\Services\QuickBookPayments\Actions\CreditCard as CreditCardActions;
use App\Services\QuickBookPayments\Objects\AccessToken;
use App\Exceptions\QuickBookPayments\QuickBookPaymentsException as QBPayException;
use GuzzleHttp\Client as GuzzleClient;
use Config;

class QBPaymentsClient
{
	use ChargesActions, CreditCardActions;
 	private $baseUrl;
 	public function __construct(AccessToken $accessToken)
	{
		$this->httpClient = new GuzzleClient(['defaults'=> ['verify' => FALSE]]);
		$this->accessTokenObject = $accessToken;
		$this->baseUrl = \Config::get('jp.quickbook.base_api_payments_url');
	}
 	public function send($url, $data)
	{
		try {
			$url = $this->getEffectiveUrl($url);

 			$response = $this->httpClient->post($url, [
				'headers' => $this->getHeaders(),
				'json' => $data,
			]);
			return $response;
 		} catch (\GuzzleHttp\Exception\ClientException $exception) {

			$responseBody = $exception->getResponse()->getBody(true);
 			$errors = json_decode($responseBody, true);
 			foreach ($errors['errors'] as $error) {
				$message = $error['message'];

				if(isset($error['moreInfo'])) {
					$message .= " Details: " . $error['moreInfo'];
				}

				throw new QBPayException($message, 1);
			}
 		} catch(\Exception $e) {
			$errors = json_decode($e->getResponse()->getBody(true), true);
			foreach ($errors['errors'] as $error) {
				throw new QBPayException($error['message'], 1);
			}
		}
 	}
 	private function attachAccessToken()
	{
		return "Bearer " . $this->accessTokenObject->getAccessToken();
	}
 	private function getHeaders()
	{
		return [
			'Authorization' => $this->attachAccessToken(),
			'Accept' => 'application/json',
			'Content-Type' => 'application/json',
			'Request-Id' => rand().uniqid(),
		];
	}
 	private function getEffectiveUrl($url)
	{
		return $this->baseUrl . $url;
	}
}
