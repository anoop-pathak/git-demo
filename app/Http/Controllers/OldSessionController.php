<?php

namespace App\Http\Controllers;

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
use League\OAuth2\Server\Exception\InvalidCredentialsException;
// use LucaDegasperi\OAuth2Server\Authorizer;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\Auth;

class OldSessionController extends ApiController
{

    protected $authorizer;

    protected $userDeviceRepo;

    public function __construct(
        Larasponse $response,
        // Authorizer $authorizer,
        UserDevicesRepository $userDeviceRepo,
        AuthenticationService $authenticationService
    ) {

        // $this->authorizer = $authorizer;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        $this->userDeviceRepo = $userDeviceRepo;
        $this->authService = $authenticationService;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        $this->middleware('auth', ['only' => ['getAuthorize', 'postAuthorize']]);
        $this->middleware('csrf', ['only' => 'postAuthorize']);
        $this->middleware('check-authorization-params', ['only' => ['getAuthorize', 'postAuthorize']]);
    }

    /**
     * start user session
     *
     * @return json response including token and user if session successfully started
     */
    public function start()
    {
        $input = Request::all();
        $validator = Validator::make($input, User::getAuthRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $isLoggedIn = \Auth::attempt([
                'email' => $input['username'], 
                'password' => $input['password']
            ]);

            if (!$isLoggedIn) {
                goto InvalidUser;
            }

            $user = Request::user();

            $tokenObj = $user->createToken('Personal Access Token');

            $token = $tokenObj->token;
            $token->expires_at = now()->addSeconds(config('auth.access_token_ttl'));
            $token->save();

            $token->token_type    = 'Bearer';
            $token->refresh_token = null;
            $token->access_token  = $tokenObj->accessToken;

            $token = $this->authService->verify($token, $user, $input);
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


        /*// save user device data..
        $input['session_id'] = $token->id;
        $device = $this->userDeviceRepo->saveDevice($user, $input);

        if ($device) {
            $device = $this->response->item($device, new UserDevicesTransformer);
        }

        // set headers for cloud front cookies..
        // CloudFront::setCookies();
        */

        return ApiResponse::accepted([
            'token' => $token,
            'user' => $this->response->item($user, new UsersTransformer),
            'device' => false,
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
}
