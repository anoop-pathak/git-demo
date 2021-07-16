<?php

namespace App\Http\Controllers;

use App\Models\QuickBook;
use App\Repositories\QuickBookRepository;
use Request;
use Illuminate\Support\Facades\Session;
use OAuth;

class IntuitsController extends Controller
{

    public function __construct(QuickBookRepository $repo)
    {
        $this->repo = $repo;
        $this->quickBook = OAuth::consumer('QuickBooks');
        parent::__construct();
    }

    public function connect()
    {

        $input = Request::all();
        // $this->quickBook->_scope = 12;

        $storage = new Session();
        if (ine($input, 'oauth_token')) {
            $quickBook = QuickBook::whereAccessToken($input['oauth_token'])
                ->first();
            $token = $storage->retrieveAccessToken('QuickBooks');

            $this->quickBook->requestAccessToken(
                $input['oauth_token'],
                $input['oauth_verifier'],
                $token->getRequestTokenSecret()
            );
            $companyId = $input['realmId'];
            $url = "/v3/company/$companyId/account/1";
            $result = json_decode($this->quickBook->request($url));
            echo 'result: <pre>' . print_r($result, true) . '</pre>';
        } else {
            $token = $this->quickBook->requestRequestToken();
            $storage->storeAccessToken('QuickBooks', $token);
            $url = $this->quickBook->getAuthorizationUri([
                'oauth_token' => $token->getRequestToken(),
                'oauth_verifier' => 'test'
            ]);
            header('Location: ' . $url);
            return $url;
        }
    }

    public function disconnect()
    {
    }
}
