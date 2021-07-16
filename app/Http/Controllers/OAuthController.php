<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Response;
use LucaDegasperi\OAuth2Server\Authorizer;

class OAuthController extends Controller
{
    protected $authorizer;

    public function __construct(Authorizer $authorizer)
    {
        $this->authorizer = $authorizer;

        $this->middleware('auth', ['only' => ['getAuthorize', 'postAuthorize']]);
        $this->middleware('csrf', ['only' => 'postAuthorize']);
        $this->middleware('check-authorization-params', ['only' => ['getAuthorize', 'postAuthorize']]);
    }

    public function postAccessToken()
    {
        return Response::json($this->authorizer->issueAccessToken());
    }

    public function getAuthorize()
    {
        return view('authorization-form', $this->authorizer->getAuthCodeRequestParams());
    }

    public function postAuthorize()
    {
        // get the user id
        $params['user_id'] = \Auth::user()->id;

        $redirectUri = '';

        if (Request::get('approve') !== null) {
            $redirectUri = $this->authorizer->issueAuthCode('user', $params['user_id'], $params);
        }

        if (Request::get('deny') !== null) {
            $redirectUri = $this->authorizer->authCodeRequestDeniedRedirectUri();
        }

        return redirect($redirectUri);
    }
}
