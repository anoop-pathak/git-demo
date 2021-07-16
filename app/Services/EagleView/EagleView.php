<?php

namespace App\Services\EagleView;

use Exception;
use GuzzleHttp\Client;
use App\Models\EVClient;
use Illuminate\Support\Carbon;
use App\Exceptions\EagleviewException;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AuthorizationException;
use GuzzleHttp\Exception\RequestException;
use App\Exceptions\ReConnectEagleViewException;
use App\Exceptions\AuthenticationFailureException;
use App\Exceptions\EagleViewNotConnectedException;

class EagleView
{

    /**
     * Guzzle Http request.
     * @var GuzzleHttp\Client
     */
    protected $request;

    function __construct()
    {
        $this->sourceId = config('eagleview.sourceId');
        $this->request = new Client([
            'base_uri' => config('eagleview.base_url'),
            'verify' => false,
            'headers' => [
                'Content-Type' => 'application/json',
                // 'EVAuthHeader' => "Basic " . $this->getEVAuthHeader(),
            ]
        ]);
    }

    /**
     * Eagle view Authentication
     * @param String $username | Username
     * @param String $password | Password
     * @return Boolean
     */
    public function authentication($username, $password)
    {

        $token = config('eagleview.token');

        $data = [
            'form_params' => [
                'username'   => $username,
                'password'   => $password,
                'grant_type' => 'password'
            ],
            'headers' => [
                'Authorization' => "Basic {$token}",
                'Content-Type'  => 'application/x-www-form-urlencoded',
            ],
        ];

        try {
            $response = $this->request->post('Token', $data);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $errorRes = json_decode($response->getBody()->getContents());
            if(isset($errorRes->error_description) && $errorRes->error_description == 'Unable to Authorize') {
                throw new AuthenticationFailureException("Authentication Failure");
            }
            if(($response->getStatusCode() == 500)) {
                throw new AuthenticationFailureException("Authentication Failure");
            }elseif($response->getStatusCode() == 401) {
                throw new AuthenticationFailureException("Unauthorized User");
            }else {
                throw new EagleviewException($response);
            }
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Get Products list
     * @param  String $username | Username
     * @return Array | products list
     */
    public function getAllProducts()
    {
        try {
            $evClient = $this->getClient();

            $params = [
                'headers' => [
                    'Authorization' => config('eagleview.access_token_type')." {$evClient->access_token}",
                ],
            ];

            $response = $this->request->get('/v2/Product/GetAvailableProducts', $params);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $errorRes = $e->getResponse();
            if( !empty($errorRes) && $errorRes->getStatusCode() == 401) {
                throw new AuthorizationException(trans('response.error.eagle_view_unauthorized'));
            }else {
                throw new EagleviewException($this->parseEagleViewExeptionMessage($errorRes));
            }
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * Place Order
     * @param  Array $details | Order Details
     * @param  int $refId | Refrence Id
     * @return Integer | ReportId
     */
    public function placeOrder($details)
    {
        try {
            $evClient = $this->getClient();
            $requestData = [
                'json' => $details,
                'headers' => [
                    'Authorization' => config('eagleview.access_token_type')." {$evClient->access_token}",
                ]
            ];
            $details['PlaceOrderUser'] = $evClient->username;
            $response = $this->request->post('v2/Order/PlaceOrder', $requestData);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $response = $e->getResponse();
            if(!empty($response) && $response->getStatusCode() == 401) {
                throw new AuthorizationException(trans('response.error.eagle_view_unauthorized'));
            }else {
                throw new EagleviewException($this->parseEagleViewExeptionMessage($response));
            }
        } catch (Exception $e) {
            throw $e;
        }

    }

    /**
     * get ev report by report id
     *
     * @param $token
     * @return response
     */
    public function getReportById($reportId)
    {
        try {
            $evClient = $this->getClient();
            $requestData = [
                'query' => [
                    'reportId' => $reportId,
                ],
                'headers' => [
                    'Authorization' => config('eagleview.access_token_type')." {$evClient->access_token}",
                ],
            ];
            $response = $this->request->get(config('eagleview.get_report_endpoint'), $requestData);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function renewToken($refreshToken)
    {
        $token = config('eagleview.token');
        try {
            $requestData = [
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                ],
                'headers' => [
                    'Authorization' => "Basic {$token}",
                    'Content-Type' => 'application/x-www-form-urlencoded',
                ]
            ];

            $response = $this->request->post('Token', $requestData);

            return json_decode($response->getBody(), true);
        } catch (Exception $e) {
            throw $e;
        }

    }

    /******************** Private Section ************************/

    private function getEVAuthHeader()
    {
        $username = config('eagleview.username');
        $password = config('eagleview.password');
        $formateString = $username . ':' . $password;

        return base64_encode($formateString);
    }

    private function parseEagleViewExeptionMessage($errorResponse)
    {
        preg_match("/<Exception>(.*?)<\\/Exception>/", $errorResponse, $match);

        // exception..
        if (!empty($match) && isset($match[1]) && !empty($match[1])) {
            return $match[1];
        }

        // check message if exception not found
        preg_match("/<Message>(.*?)<\\/Message>/", $errorResponse, $match);

        if (!empty($match) && isset($match[1]) && !empty($match[1])) {
            return $match[1];
        } else {
            return trans('response.error.something_wrong');
        }
    }

    private function getClient()
    {
        $evClient = EVClient::where('company_id', getScopeId())
            ->first();
        try {
            if(!$evClient) {
                throw new EagleViewNotConnectedException("EagleView not connected.");                 
            }
            $now = Carbon::now()->toDateTimeString();
            if($now <= $evClient->token_expiration_date) return $evClient;
            $response = $this->renewToken($evClient->refresh_token);
            $evClient->access_token             = $response['access_token'];
            $evClient->refresh_token            = $response['refresh_token'];
            $evClient->token_expiration_date    = Carbon::parse($response['.expires'])->toDateTimeString();
            $evClient->save();
            
        } catch (RequestException $e) {
            $response = $e->getResponse();
            $errRes   = json_decode($response->getBody()->getContents());
            if($response->getStatusCode() == 400 && (isset($errRes->error) && $errRes->error == "invalid_grant")) {
                throw new ReConnectEagleViewException(trans('response.error.reconnect', ['attribute' => 'Eagleview']));
            }
            if(!empty($response) && $response->getStatusCode() == 401) {
                throw new UnauthorizedException(trans('response.error.eagle_view_unauthorized'));
            }else {
                throw new EagleviewException($this->parseEagleViewExeptionMessage($response));
            } 
        } catch (Exception $e) {
            throw $e;   
        }
        return $evClient;
    }
}
