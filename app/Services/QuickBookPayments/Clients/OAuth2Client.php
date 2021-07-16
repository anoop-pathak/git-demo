<?php

namespace App\Services\QuickBookPayments\Clients;

use GuzzleHttp\Client as GuzzleClient;
use App\Services\QuickBookPayments\Objects\AuthToken as AuthToken;
use App\Services\QuickBookPayments\Actions\AccessToken as AccessToken;
use App\Exceptions\QuickBookOAuth2Exception as QBOAuth2Exception;
use App\Exceptions\QuickBookException as QuickBookException;
use Config;
use Illuminate\Support\Facades\Auth;

class OAuth2Client
{
    use AccessToken;
 	private $clientId;
	private $clientSecret;
	private $scopes;
	private $state;
	private $grantType;
	private $responseType;
    private $withPaymentsScope = false;
	private $callbackUrl;
	private $authUrl;
 	public function __construct()
	{
		$this->httpClient = new GuzzleClient(['defaults'=> ['verify' => FALSE, 'debug' => true]]);
		$this->clientId = Config::get('jp.quickbook.client_id');
		$this->clientSecret = Config::get('jp.quickbook.client_secret');
		$this->scopes = Config::get('jp.quickbook.scopes');
 		$this->authUrl = Config::get('jp.quickbook.auth_url');
		$this->callbackUrl = Config::get('jp.quickbook.redirect_uri');
		$this->grantType = Config::get('jp.quickbook.grant_type');
		$this->responseType = Config::get('jp.quickbook.response_type');
	}
     /**
     * This method will send the request to the specified URL and will add the data to the Request as provided to the method
     * @param  String $url     The URL where request needs to be made
     * @param  string $method  GET or POST request
     * @param  array  $data    The data to sent to the server, which will be added to the request accordingly
     * @param  array  $headers The headers to be added to the request
     * @return ResponseBody The response returned from the server as a ResponseBody Object
     */
    public function sendRequest($url, $method = 'get', $data = [], $headers = [])
    {
        try {
            if($method == 'get') {
                $response = $this->httpClient->request('GET', $url, [
                    'query' => $data,
                    'headers' => $headers,
                ]);
            } else {
                $response = $this->httpClient->request('POST', $url, [
                    'form_params' => $data,
                    'headers' => $headers,
                ]);
            }

        } catch (\Exception $e) {
            $error = json_decode($e->getResponse()->getBody());
            if($error->error == 'invalid_grant') {
                throw new QuickBookException("Unauthorized, please reconnect to Quickbook.");
            }

            throw new QBOAuth2Exception($error->error);
        }
 		return $response->getBody();
	}
 	public function authorizationUrl($withPaymentsScope = false)
	{
        $this->withPaymentsScope = $withPaymentsScope;
 		$data = [
			'client_id' => $this->getClientId(),
			'scope' => $this->getScope(),
			'state' => $this->getState(),
			'redirect_uri' => $this->getCallbackUrl(),
			'grant_type' => $this->getGrantType(),
			'response_type' => $this->getResponseType(),
			'state' => $this->getState()
		];
        return $this->authUrl . "?" . http_build_query($data);
	}
 	/**
     * @return mixed
     */
	public function captureAuthToken($callbackUrl)
	{
        $callback = AuthToken::getFromCallbackUrl($callbackUrl);
        $authToken = new AuthToken;
        $authToken
                ->setRedirectUri($this->getCallbackUrl())
                ->setGrantType('authorization_code')
                ->setCode($callback['code'])
                ->setRealmId($callback['realmId'])
                ->setState($callback['state']);
         return $authToken;
	}
     /**
     * @return mixed
     */
    public function getClientId()
    {
        return $this->clientId;
    }
     /**
     * @param mixed $clientId
     *
     * @return self
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
         return $this;
    }
     /**
     * @return mixed
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }
     /**
     * @param mixed $clientSecret
     *
     * @return self
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
         return $this;
    }
     /**
     * @return mixed
     */
    public function getScope()
    {
        if(!$this->withPaymentsScope) {
            $this->removePaymentsFromScope();
        }

        return implode(" ", $this->scopes);
    }
     /**
     * @return mixed
     */
    public function getScopes()
    {
        return $this->scopes;
    }
     /**
     * @param mixed $scopes
     *
     * @return self
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
         return $this;
    }
     /**
     * @return mixed
     */
    public function getState()
    {
        $state = [
            'company' => Auth::user()->company->id,
            'current_login_user_id' => Auth::user()->id,
        ];
        if($this->withPaymentsScope) {
            $state['with_payments'] = true;
        }
         return json_encode($state);
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
     /**
     * @return mixed
     */
    public function getGrantType()
    {
        return $this->grantType;
    }
     /**
     * @param mixed $grantType
     *
     * @return self
     */
    public function setGrantType($grantType)
    {
        $this->grantType = $grantType;
         return $this;
    }
     /**
     * @return mixed
     */
    public function getCallbackUrl()
    {
        return $this->callbackUrl;
    }
     /**
     * @param mixed $callbackUrl
     *
     * @return self
     */
    public function setCallbackUrl($callbackUrl)
    {
        $this->callbackUrl = $callbackUrl;
         return $this;
    }
     /**
     * @return mixed
     */
    public function getAuthUrl()
    {
        return $this->authUrl;
    }
     /**
     * @param mixed $authUrl
     *
     * @return self
     */
    public function setAuthUrl($authUrl)
    {
        $this->authUrl = $authUrl;
         return $this;
    }
     /**
     * @return mixed
     */
    public function getAccessTokenUrl()
    {
        return $this->accessTokenUrl;
    }
     /**
     * @param mixed $accessTokenUrl
     *
     * @return self
     */
    public function setAccessTokenUrl($accessTokenUrl)
    {
        $this->accessTokenUrl = $accessTokenUrl;
         return $this;
    }
     /**
     * @return mixed
     */
    public function getResponseType()
    {
        return $this->responseType;
    }
     /**
     * @param mixed $responseType
     *
     * @return self
     */
    public function setResponseType($responseType)
    {
        $this->responseType = $responseType;
         return $this;
    }
     private function removePaymentsFromScope()
    {
        $key = array_search('com.intuit.quickbooks.payment', $this->scopes);
        if($key>=0) {
            unset($this->scopes[$key]);
        }
    }
} 