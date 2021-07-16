<?php

namespace App\Services\SkyMeasure;

use App\Exceptions\SkyMeasureError;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class SkyMeasure
{
    // Response Codes
    const INVALID_CREDENTIALS = 2;

    // Methods
    const Login = 'Login';
    const ChangePassword = 'ChangePassword';
    const ForgotPassword = 'ForgotPassword';
    const ValidateUsername = 'ValidateUsername';
    const ValidateToken = 'ValidateToken';
    const CreateNewUser = 'CreateNewUser';
    const GetUserProfile = 'GetUserProfile';
    const UpdateUserProfile = 'UpdateUserProfile';
    const SubmitOrder = 'SubmitOrder';
    const CreateOrder = 'CreateOrder';
    const GetOrders = 'GetOrders';
    const GetReports = 'GetReports';
    const GetReportsInfo = 'GetReportsInfo';
    const GetReportsFile = 'GetReportsFile';
    const OrderReceived = 'OrderReceived';
    const OrderTransaction = 'OrderTransaction';

    /**
     * Guzzle Http request.
     * @var GuzzleHttp\Client
     */
    protected $request;

    function __construct()
    {
        $this->request = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
            ]
        ]);
    }

    /**
     * Authentication
     * @param  string $username | Username
     * @param  string $password | Paswwrod
     * @return string $token
     */
    public function authentication($username, $password)
    {
        try {
            $response = $this->request([
                "Method" => self::Login,
                "Username" => $username,
                "Password" => $password,
            ]);
            $response = json_decode($response->getBody(), 1);

            $this->parseSkyMeasureResponse(self::Login, $response);

            if (isset($response['LoginResponse']['LoginResult']['token'])) {
                return $response['LoginResponse']['LoginResult']['token'];
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Place Order
     * @param  string $token | Token
     * @param  array $data | Order data
     * @return $orderId
     */
    public function placeOrder($token, $data)
    {
        try {
            $data["Method"] = self::CreateOrder;
            $data["Token"] = $token;
            $response = $this->request($data);
            $response = json_decode($response->getBody(), 1);

            $this->parseSkyMeasureResponse(self::CreateOrder, $response);

            if (isset($response['CreateOrderResponse']['CreateOrderResult']['OrderID'])) {
                return $response['CreateOrderResponse']['CreateOrderResult']['OrderID'];
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Orders List
     * @param  string $token | Token
     * @param  array $filters | Filters Array
     * @return json response
     */
    public function getOrders($token, $filters = [])
    {
        try {
            $response = $this->request([
                "Method" => self::GetOrders,
                "Token" => $token,
                "Page" => 0,
                "Size" => 100,
            ]);

            $this->parseSkyMeasureResponse(self::GetOrders, $response);

            return json_decode($response->getBody(), 1);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Report Files List..
     * @param  string $token | Token
     * @param  string $orderId | Order Id
     * @return json response
     */
    public function getReportFilesList($token, $orderId)
    {
        try {
            $response = $this->request([
                "Method" => self::GetReportsInfo,
                "Token" => $token,
                "OrderID" => $orderId,
            ]);

            $response = json_decode($response->getBody(), 1);

            $this->parseSkyMeasureResponse(self::GetReportsInfo, $response);

            if (isset($response['GetReportsInfoResponse']['GetReportsInfoResult']['Files'])) {
                return $response['GetReportsInfoResponse']['GetReportsInfoResult']['Files'];
            }

            return [];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Get Report File data
     * @param  string $token | Token
     * @param  string $orderId | Order Id
     * @param  string $fileId | File Id
     * @return content
     */
    public function getReportFile($token, $orderId, $fileId)
    {
        try {
            $response = $this->request([
                "Method" => self::GetReportsFile,
                "Token" => $token,
                "OrderID" => $orderId,
                "FileID" => $fileId,
            ]);

            return $response->getBody()->getContents();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Signup
     * @param  array $data | Signup Data
     * @return boolean
     */
    public function signup($data)
    {
        try {
            $data["Method"] = self::CreateNewUser;
            $response = $this->request($data);
            $response = json_decode($response->getBody(), 1);
            $this->parseSkyMeasureResponse(self::CreateNewUser, $response);

            return true;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /*************** Private Section ****************/

    private function request($requestBody)
    {
        return $this->request->post(config('skymeasure.url'), [
            'json' => $requestBody,
        ]);
    }

    private function parseSkyMeasureResponse($method, &$response)
    {
        $responseCode = null;
        $responseMessage = trans('response.error.something_wrong');
        $result = [];
        if (isset($response["{$method}Response"]["{$method}Result"])) {
            $result = $response["{$method}Response"]["{$method}Result"];
        }

        if (isset($result["response"]) && isset($result["message"])) {
            $responseCode = isset($result['response']) ? $result['response'] : null;
            $responseCode = isset($result['status']) ? $result['status'] : $responseCode;
            $responseMessage = $result['message'];
            
            goto ValidateResponseCode;
        }

        if (isset($result["Response"]) && isset($result["Response"]["ResponseCode"])) {
            $responseCode = $result['Response']['ResponseCode'];
            $responseMessage = $result['Response']['ResponseMessage'];
            goto ValidateResponseCode;
        }

        ValidateResponseCode:
        if (empty($responseCode)) {
            Log::error('SkyMeasure Unhandled Response: ' . json_encode($response));
        }

        if ($responseCode != "1") {
            throw new SkyMeasureError($responseMessage);
        }
    }
}
