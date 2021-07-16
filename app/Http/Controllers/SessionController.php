<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request as HttpRequest;

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportOAuthController;

use App\Exceptions\InactiveAccountException;
use App\Exceptions\InActiveUserException;
use App\Exceptions\InvalidClientSecretException;
use App\Exceptions\LoginNotAllowedException;
use App\Exceptions\SuspendedAccountException;
use App\Exceptions\TerminatedAccountException;
use App\Exceptions\UnsubscribedAccountException;
use App\Helpers\SecurityCheck;
use App\Models\ApiResponse;
use App\Models\User;
use App\Repositories\UserDevicesRepository;
use App\Services\Users\AuthenticationService;
use App\Transformers\UserDevicesTransformer;
use App\Transformers\UsersTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Validator;
/**
 * @todo This Exception needs to be replaced with Passport's Exception as the OAuth library has changed,
 */
use League\OAuth2\Server\Exception\InvalidCredentialsException;
// use LucaDegasperi\OAuth2Server\Authorizer;
use Psr\Http\Message\ServerRequestInterface;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\Auth;

use Laravel\Passport\TokenRepository as PassportTokenRepo;
use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use App\Helpers\CloudFrontSignedCookieHelper as CloudFront;
use Illuminate\Support\Facades\Crypt;
use Config;
use GuzzleHttp\Client;
use App\Transformers\Optimized\ServerlessUserInfoTransformer;

class SessionController extends PassportOAuthController
{
    protected $authorizer;

    protected $userDeviceRepo;

    public function __construct(
        Larasponse $response,
        // Authorizer $authorizer,
        UserDevicesRepository $userDeviceRepo,
        AuthenticationService $authenticationService,
        PassportTokenRepo $tokenRepo,
        JwtParser $jwt,
        AuthorizationServer $authServer
    ) {
        parent::__construct($authServer, $tokenRepo, $jwt);

        // $this->authorizer = $authorizer;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        $this->userDeviceRepo = $userDeviceRepo;
        $this->authService = $authenticationService;
        $this->openAPIRequest = new Client(['base_url' => config('jp.open_api_url')]);


        // $this->middleware('auth', ['only' => ['getAuthorize', 'postAuthorize']]);
        // $this->middleware('csrf', ['only' => 'postAuthorize']);
        // $this->middleware('check-authorization-params', ['only' => ['getAuthorize', 'postAuthorize']]);
    }

    /**
     * start user session
     *
     * @return json response including token and user if session successfully started
     */
    public function start(ServerRequestInterface $request)
    {
        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        $input = Request::all();
        $validator = Validator::make($input, User::getAuthRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $query = User::where('email' , $input['username']);
            if (ine($input, 'company_id')) {
                $query->where('company_id', $input['company_id']);
            }

            $clientId = ine($input, 'client_id') ? $input['client_id'] : null;

            if($clientId == config('jp.mobile_client_id')) {
                Config::set('is_mobile', true);
            }

            $user = $query->active()->login()->first();

            if(!$user) goto InvalidUser;

            // checking the active company of a user if he belongs to multiple companies
            if($user && $user->multiple_account) {
                $activeCompany = User::getMultiUserActiveCompany($user->email);
                $user = User::where('email' , Request::get('username'))
                    ->active()
                    ->login();

                if($activeCompany) {
                    $user->where('company_id', $activeCompany->id);
                }

                $user = $user->first();
            }

            $isLoggedIn = \Auth::attempt([
                'email' => $input['username'],
                'password' => $input['password'],
                'active'   => true,
            ]);

            // Add condition for Owner and Admin
            if(($input['client_id'] == config('jp.spotio_client_id'))) {
                if(!$user->isOwner() && !$user->isAdmin()) {
                    goto InvalidUser;
                }
            }

            if ((!$isLoggedIn)
                || (($input['client_id'] == config('jp.mobile_client_id'))
                && ($user && $user->isSubContractor()))) {
                goto InvalidUser;
            }

            $existingTokenIds = $user->tokens()->pluck('id')->toArray();

            // add company id with username and create a new request instance
            $data = $request->getParsedBody();
            $userName = $input['username'];
            if (ine($input, 'company_id')) {
                $data['username'] = $userName.', '.$input['company_id'];
            }
            $newRequest = $request->withParsedBody($data);
            $token = $this->issueToken($newRequest);
            $token = json_decode($token->getContent(), 1);

            \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            $token = $this->authService->verify($token, $user, $input, $existingTokenIds);

        } catch (InvalidCredentialsException $e) {
            InvalidUser :
            return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'email or password']));
        } catch (InActiveUserException $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (InactiveAccountException $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (SuspendedAccountException $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (TerminatedAccountException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (UnsubscribedAccountException $e) {
            return ApiResponse::errorUnauthorized($e->getMessage());
        } catch (LoginNotAllowedException $e) {
            return ApiResponse::errorForbidden($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal($e->getMessage(), $e);
        }

        # save user device data..
        $device = $this->userDeviceRepo->saveDevice($user, $input);
        if ($device) {
            $device = $this->response->item($device, new UserDevicesTransformer);
        }

        # set headers for cloud front cookies..
        CloudFront::setCookies();
        // TODO - need to verify on qa
        $accessToken = Crypt::encryptString($token['access_token']);
        $domain = config('cloud-front.COOKIES_DOMAIN');
        header("Set-Cookie: access_token=$accessToken; path=/; domain=$domain; secure; httpOnly", false);

        return ApiResponse::accepted([
            'token' => $token,
            'user' => $this->response->item($user, new UsersTransformer),
            'device' => $device,
            'is_restricted' => SecurityCheck::RestrictedWorkflow($user),
        ]);
    }

    /**
     * Third party login form
     * @return [type] [description]
     */
    public function loginForm()
    {
        $input = Request::onlyLegacy(
            'client_id',
            'client_secret',
            'grant_type',
            'redirect_uri',
            'domain',
            '_wpnonce'
        );
        try {
            $this->authService->checkPluginClient($input);

            return view('oauth.authorization-form', ['params' => $input]);
        } catch (InvalidClientSecretException $e) {
            $jsonResponse = ApiResponse::errorUnauthorized($e->getMessage());
        } catch (\Exception $e) {
            $jsonResponse = ApiResponse::errorInternal($e->getMessage());
        }

        $arrayResponse = json_decode(json_encode($jsonResponse->getData()), true);
        $redirectUrl = $input['redirect_uri'] . '&' . http_build_query($arrayResponse);

        return redirect($redirectUrl);
    }

    /**
     * Third party authetication
     * @return [type] [description]
     */
    public function authentication()
    {
        $input = Request::onlyLegacy(
            'client_id',
            'client_secret',
            'username',
            'password',
            'grant_type',
            'redirect_uri',
            'domain',
            '_wpnonce'
        );

        try {
            $token = $this->authorizer->issueAccessToken();
            $user = User::where('email', Request::get('username'))->first();
            $token = $this->authService->verify($token, $user, $input);
            $token['user_id'] = $user->id;
            $redirectUrl = $input['redirect_uri'] . '&' . http_build_query($token);
            $redirectUrl .= '&_wpnonce=' . $input['_wpnonce'];
            return redirect($redirectUrl);
        } catch (LoginNotAllowedException $e) {
            $errorMessage = $e->getMessage();
        } catch (InvalidClientSecretException $e) {
            $errorMessage = $e->getMessage();
        } catch (InvalidCredentialsException $e) {
            $errorMessage = trans('response.error.invalid_email_password');
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
        }
        return redirect(URL::previous())->withInput()->withErrors($errorMessage);
    }

    /**
     * renew access token using refresh token.
     * @return [type] [description]
     */
    public function renewToken()
    {
        $token = $this->authorizer->issueAccessToken();

        return ApiResponse::success([
            'token' => $token,
        ]);
    }

    /**
     * logout
     *
     * @return Response
     */
    public function logout()
    {
        try {
            $input = Request::onlyLegacy('device_id', 'domain');
            $this->authService->destroy($input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.logout')
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.something_wrong'), $e);
        }
    }

    /**
     * Get open open token / generate token
     * @return token
     */
    public function getOpenAPIToken()
    {
        $input = Request::all();

        $validator = Validator::make( $input, ['name' => 'required']);

        if($validator->fails() ){
            return ApiResponse::validation($validator);
        }

        $userId = Auth::user()->id;

        $data = [
            'user_id' => $userId,
            'name'    => $input['name']
        ];

        $response = $this->openAPIRequest->get('api/v1/third_party/token/all', [
            'query' => $data
        ])->json();

        if($response && ine($response, 'tokens') && count($response['tokens']) >= 5) {
            return ApiResponse::errorGeneral('Maximum token limit reached!');
        }

        return $this->openAPIRequest->post('api/v1/third_party/token', [
            'body' => $data
        ])->json();
    }

    /**
     * Get open api token list
     * @return token
     */

    public function getOpenAPITokenList()
    {
        $userId = Auth::user()->id;

        $data = [
            'user_id' => $userId,
        ];

        return $this->openAPIRequest->get('api/v1/third_party/token/all', [
            'query' => $data
        ])->json();
    }

    /**
     * Revoke open api token
     * @return json
     */
    public function revokeOpenAPIToken()
    {
        $input = Request::all();

        $validator = Validator::make($input, [
            'token_id' => 'required'
        ]);

        if ($validator->fails()) {

            return ApiResponse::validation($validator);
        }

        $userId = Auth::user()->id;

        $data = [
            'user_id'   => $userId,
            'token_id'  => $input['token_id']
        ];

       return  $this->openAPIRequest->post('api/v1/third_party/token/revoke', [
            'body' => $data
        ])->json();
    }

    /**
     * Get currently loggedin use detail.
     *
     * @return json
     */
    public function getSlsLoggedInUser()
    {
        $user = $this->response->item(Auth::user(), new ServerlessUserInfoTransformer);
        return ApiResponse::success(['data' => $user]);
    }
}
