<?php

namespace App\Services\TimeZone;

use GuzzleHttp\Client;

class TimeZone
{

    protected $request;

    protected $url = 'https://maps.googleapis.com/maps/api/timezone/json';

    protected $location;

    public function __construct($location)
    {
        $this->request = new Client(['verify' => false]);
        $this->apiKey = config('google.server_api_key');
        $this->location = $location;
    }

    public function get()
    {
        $location = geocode($this->location);
        if (!$location || !ine($location, 'lat') || !ine($location, 'lng')) {
            return false;
        }

        $url = $this->url . '?location=' . $location['lat'] . ',' . $location['lng'] . '&timestamp=' . timestamp() . '&key=' . $this->apiKey;
        $respose = $this->request->get($url, [
            'timeout' => 3 //3 seconds
        ]);

        $data = (object)$respose->getBody();

        return $data;
    }
}
