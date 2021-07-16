<?php namespace App\Services\Google;

use Google_Client;
use App\Models\Email;
use App\Models\GoogleClient;
use App\Repositories\GoogleClientRepository;
use App\Services\Google\GoogleCalenderServices;
use App\Exceptions\DuplicateGoogleAccountTwoWaySyncing;

class GoogleConnectService
{

    protected $client;
    protected $repo;
    protected $calenderService;

    public function __construct(GoogleClientRepository $repo, GoogleCalenderServices $calenderService)
    {
        $this->client = new Google_Client();
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->client->setApplicationName(config('google.app_name'));
        $this->client->setClientId(config('google.client_id'));
        $this->client->setClientSecret(config('google.client_secret'));
        $this->client->setDeveloperKey(config('google.api_key'));
        $this->client->setRedirectUri(config('google.redirect_url'));
        $this->repo = $repo;
        $this->calenderService = $calenderService;
    }

    /**
     * @param $state Json String | include data in state param of url
     * @return Google Auth Url
     */
    public function getAuthUrlForCalendarAccess($stateData = [])
    {
        $scopes = $this->prepareScopes($stateData);
        $state = json_encode($stateData);
        $this->client->setScopes($scopes);
        $this->client->setState($state);
        return $this->client->createAuthUrl();
    }

    /**
     * @param $state Json String | include data in state param of url
     * @return Google Auth Url
     */
    public function getAuthUrlForDriveAccess($state)
    {
        $this->client->setScopes(config('google.scopes.drive_and_sheets'));
        $this->client->setState($state);
        return $this->client->createAuthUrl();
    }

    /**
     * It use to integrate google account and google calender..
     * @param $code integer | get code for google authentication
     * @param $stateData Json String | state data return after google authentication..
     * @return Google Client Model object
     */
    public function googleAccountConnect($code, $stateData)
    {
        $stateData = json_decode($stateData);

        $accessToken = $this->getAccessToken($code);

        $email = $this->getClientEmailId($accessToken);

        $googleClient = null;

        //checking for two way syncing for user calendar accounts
        if (isset($stateData->user_id)) {
            $googleClient = GoogleClient::where('email', $email)
                ->whereNull('company_id')
                ->calendar()
                ->whereNotNull('channel_id')
                ->first();
        }


        if ($googleClient) {
            throw new DuplicateGoogleAccountTwoWaySyncing("Please choose different account.");
        }

        $googleClient = $this->repo->saveClientData($accessToken, $email, $stateData);

        if (isset($stateData->user_id)) {
            // calender watch for user account..
            $this->calenderIntigration($googleClient);
        }

        return $googleClient;
    }

    public function accountDisconnnect($gClient)
    {
        try {
            $this->client->setAccessToken($gClient->token);
            $this->client->revokeToken();
        } catch (\Exception $e) {
            //exception handle
        }
    }

    /**
     * This function will authenticate the code and return access token
     * @param $code integer | get code for google authentication
     */
    public function getAccessToken($code)
    {
        $this->client->authenticate($code);
        return $this->client->getAccessToken();
    }

    /************** Private Section ***************/

    /**
     * It will find google auth client's email id
     * @param $accessToken string | Google offile access token
     * @return Email id
     */
    private function getClientEmailId($accessToken)
    {
        $this->client->setAccessToken($accessToken);
        $oauth = new \Google_Service_Oauth2($this->client);
        return $oauth->userinfo->get()->email;
    }

    private function calenderIntigration(GoogleClient $googleClient)
    {
        // use default calendar..
        $calenderId = $googleClient->email; // use default calendar..
        $this->repo->saveCalenderDetails($googleClient->user_id, $calenderId);
        return true;
    }

    private function prepareScopes($data)
    {
        $scopes = [];

        if (ine($data, 'scope_calendar_and_tasks')) {
            $scopes = array_merge($scopes, config('google.scopes.calendar'));
        }

        if (ine($data, 'scope_drive')) {
            $scopes = array_merge($scopes, config('google.scopes.drive'));
        }

        if (ine($data, 'scope_gmail')) {
            $scopes = array_merge($scopes, config('google.scopes.gmail'));
        }

        return arry_fu($scopes);
    }
}
