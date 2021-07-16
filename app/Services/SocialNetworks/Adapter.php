<?php

namespace App\Services\SocialNetworks;

use Hybridauth\Hybridauth;
use Hybridauth\Storage\Session;
use Hybridauth\Provider\Twitter;
use Hybridauth\Provider\Facebook;

class Adapter
{

    public function __construct()
    {
        $this->oauth = new Hybridauth(config('hybridauth'));
        $this->session = new Session;
    }

    protected function connection()
    {
        return $this->oauth;
    }

    public function facebook($token)
    {
        $adapter = new Facebook(config('hybridauth.providers.Facebook'));
        $tokens = [
            'access_token' => $token['client_token']
        ];
        $adapter->setAccessToken($tokens);

        return $adapter;
    }

    public function twitter($token)
    {
        $adapter = new Twitter(config('hybridauth.providers.Twitter'));

        $tokens = [
            'access_token' => $token['client_token'],
            'access_token_secret' => $token['client_secret']
        ];

        $adapter->setAccessToken($tokens);

        return $adapter;
    }

    public function verify($network)
    {
        return $this->oauth->isConnectedWith($network);
    }

    public function logout()
    {
        $this->oauth->logoutAllProviders();
    }
}
