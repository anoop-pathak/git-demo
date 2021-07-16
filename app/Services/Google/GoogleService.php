<?php namespace App\Services\Google;

use Google_Client;
use App\Models\GoogleClient;
use App\Repositories\GoogleClientRepository;
use App\Services\Google\GoogleCalenderServices;

class GoogleService
{

    protected $client;
    protected $repo;
    protected $calenderService;

    public function __construct(GoogleClientRepository $repo, GoogleCalenderServices $calenderService)
    {
        $this->client = new Google_Client();
        $this->repo = $repo;
        $this->calenderService = $calenderService;
    }

    /**
     * This function create the new caleder of user, create its channel and find sync token.
     * It use GoogleCalenderService to accomplish the process..
     * @param $googleClient Instance of GoogleClient Model | It use google client saved data for further process.
     */
    public function calenderIntigration(GoogleClient $googleClient)
    {

        if ($googleClient->channel_id) {
            $this->deleteCalenderChannel($googleClient);
        }

        ///$calenderId = $this->calenderService->createNewCalender($googleClient->token); // create new calendar..
        $calenderId = $googleClient->email; // use default calendar..
        $channelData = $this->calenderService->calenderWatch($googleClient->user_id, $calenderId, $googleClient->token);
        if (!$channelData) {
            // save only calender id
            $this->repo->saveCalenderDetails($googleClient->user_id, $calenderId);
            return true;
        }

        $syncToken = $this->calenderService->calenderFisrtSync($calenderId, $googleClient->token);

        // save calender id with channel data
        $this->repo->saveCalenderDetails(
            $googleClient->user_id,
            $calenderId,
            $syncToken,
            $channelData['id'],
            $channelData['expiration'],
            $channelData['resourceId']
        );
    }

    public function deleteCalenderChannel($googleClient)
    {
        $this->calenderService->stopCalendarWatch(
            $googleClient->channel_id,
            $googleClient->resource_id,
            $googleClient->token
        );
        $googleClient->next_sync_token = null;
        $googleClient->channel_id = null;
        $googleClient->channel_expire_time = null;
        $googleClient->resource_id = null;
        $googleClient->save();
    }
}
