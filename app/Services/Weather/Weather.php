<?php

namespace App\Services\Weather;

use GuzzleHttp\Client;
use Cache;

class Weather
{
    protected $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
        // TODO: how is this injected, pass verify config option there...
//        $client->setDefaultOption('verify', false);
    }

    public function getWeather($location, $countryCode, $unit = 'f')
    {
        $ret = [
            'city'    => [],
            'weather' => [],
        ];
        $cacheKey = "{$location}-{$countryCode}-{$unit}";
        if(Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $apiKey  = config('weather.api_key');
        $baseUrl = config('weather.base_url');
        $cityUrl = "{$baseUrl}/locations/v1/cities/{$countryCode}/search?apikey={$apiKey}&q={$location}";
        // get city for location key
        $cityList = $this->client->get($cityUrl);
        $cityList = array_values(json_decode($cityList->getBody(), 1));
        if(empty($cityList)) return $ret;
        $locationKey = isset($cityList[0]) ? $cityList[0]['Key'] : null;
        // get weather by city locatoin key
        $weatherUrl  = "{$baseUrl}/forecasts/v1/daily/1day/{$locationKey}?apikey={$apiKey}&details=true";
        // for celsius
        if($unit == 'c') {
            $weatherUrl .= "&metric=true";
        }
        $weatherRes = $this->client->get($weatherUrl);
        $weather = json_decode($weatherRes->getBody(), 1);

        $ret['city']    = $cityList[0];
        $ret['weather'] = $weather;

        // cache weather result for 15 minutes
        Cache::put($cacheKey, $ret, config('weather.refresh_time_limit'));

        return $ret;
    }
}
