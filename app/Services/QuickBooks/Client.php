<?php

namespace App\Services\QuickBooks;

use GuzzleHttp\Client as GuzzleClient;
use App\Services\QuickBookPayments\Clients\OAuth2Client;

class Client
{
    private $version = '1.0';

    public $quickbookCompanyId = null;

    public function __construct()
    {
        $this->request = new GuzzleClient([
            'base_uri' => config('jp.quickbook.base_api_url'),
            'verify' => false
        ]);
        $this->oauth2client = new OAuth2Client;
    }

    public function request($path, $method = 'GET', $body = [], $extraParam = [])
    {
        $path = $this->token->quickbook_id . $path;
        switch ($method) {
            case 'POST':
                return $this->post($path, $body, $extraParam);
                break;
            case 'GET':
                return $this->get($path, $body);
                break;
            default:
                break;
        }
    }

    public function get($path, $body, $context = 'accounting')
    {
        $authorizationHeader = $this->buildAuthorizationHeaderForAPIRequest(
            $this->getToken()
        );

        try {
            $response = $this->request->get(
                config('jp.quickbook.base_api_url') . $path . '?' . http_build_query($body),
                [
                    'headers' => [
                        'Authorization' => $authorizationHeader,
                        'Content-Type' => 'application/json',
                        'Accept' => 'application/json',
                        'Request-Id' => time()
                    ]
                ]
            );

            // $response = $this->request->send($request);
        } catch (\Exception $e) {
            if($e->getCode() == 500) {
                throw new \Exception("Quickbooks Server Error (500): " . $e->getMessage(), 0);
            }
            throw $e;
        }

        $result = $response->getBody()->getContents();
        $queryResponse = json_decode($result, true);
        return $queryResponse;
    }

    public function createInvoicePdf($path)
    {
        $path = $this->token->quickbook_id . $path;
        $authorizationHeader = $this->buildAuthorizationHeaderForAPIRequest(
            $this->getToken()
        );
        $response = $this->request->get(config('jp.quickbook.base_api_url') . $path, [
            'headers' => [
                'Authorization' => $authorizationHeader,
                'Content-Type' => 'application/pdf',
                'Request-Id' => time()
            ]
        ]);
        $result = $response->getBody()->getContents();
        return $result;
    }

    public function post($path, $body, $extraParam = [], $context = 'accounting')
    {

        $authorizationHeader = $this->buildAuthorizationHeaderForAPIRequest(
            $this->getToken()
        );

        $url = config('jp.quickbook.base_api_url') . $path;
        if (!empty($extraParam)) {
            $url .= '?' . http_build_query($extraParam);
        }
        $response = $this->request->post(
            $url,
            [
                'json' => $body,
                'headers' => [
                    'Authorization' => $authorizationHeader,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                    'Request-Id' => time()
                ]
            ]
        );

        $result = $response->getBody()->getContents();
        return json_decode($result, true);
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setQuickbookCompanyId($id)
    {
        return $this->quickbookCompanyId = $id;
    }

    public function getQuickbookCompanyId()
    {
        return $this->quickbookCompanyId;
    }

    public function refreshAccessToken($accessToken)
    {
        return $this->oauth2client->refreshAccessToken($accessToken);
    }

    public function getAuthorizationUrl($withPaymentsScope = false)
    {
        return $this->oauth2client->authorizationUrl($withPaymentsScope);
    }

    public function revokeAccessToken($accessToken)
	{
		return $this->oauth2client->revokeAccessToken($accessToken);
	}

    /**
     * This method will return the Authorisation Header by appending Bearer to the Access token from the Access Token object
     * @param $token
     * @return String
     */
    private function buildAuthorizationHeaderForAPIRequest($token)
    {
        return "Bearer " . $token->access_token;
    }

    /**
     * @param mixed $state
     *
     * @return self
     */
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }
}
