<?php

namespace App\Http\OpenAPI\Controllers; 

use Laravel\Passport\Http\Controllers\AccessTokenController as PassportOAuthController;
use App\Models\ApiResponse;
use App\Repositories\UserRepository;
use Request;
use Illuminate\Support\Facades\Validator;
use Psr\Http\Message\ServerRequestInterface;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\Auth;

use Laravel\Passport\TokenRepository as PassportTokenRepo;
use Lcobucci\JWT\Parser as JwtParser;
use League\OAuth2\Server\AuthorizationServer;
use App\Models\OauthAccessToken;
 

class SessionController extends PassportOAuthController
{
    protected $authorizer;

    protected $userDeviceRepo;

    private $userRepo;

    public function __construct(
        Larasponse $response,
        PassportTokenRepo $tokenRepo,
        JwtParser $jwt,
        AuthorizationServer $authServer,
        UserRepository $userRepo
    ) {
        parent::__construct($authServer, $tokenRepo, $jwt);

        $this->response = $response;
        
        $this->userRepo = $userRepo;
    }

    /**
     * Get Open API token
     * 
     */

    public function getToken(Request $request) 
    {

        $input = Request::all();

        $validator = Validator::make($input, [
            'name' => 'required'
        ]);
        
        if ($validator->fails()) {
            
            return ApiResponse::validation($validator);
        }

        $loggedInUser = Auth::user(); 

        $companyId = $loggedInUser->company_id;

        //If company do not has open api create one
        if( !$this->userRepo->hasOpenAPIUser($companyId)) {
            
            $userDetails = [

            ];

            $this->userRepo->createOpenAPIUser($loggedInUser->company, $userDetails, [], null);

        }

        $tokens = $this->getTokens();

        // Restrict the users to generate unlimited tokens
        if( count($tokens) >= 5) { 
            return ApiResponse::errorGeneral('Maximum token limit reached!');
        }

        $openAPIUser = $this->userRepo->getOpenAPIUser($companyId);

        \DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        $token = $openAPIUser->createToken($input['name']);
        
        \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        return $token;
    }

    /**
     * Revoke Open API token
     *  @return Response
     */

    public function revokeToken() 
    {   
        $input = Request::all();

        $validator = Validator::make($input, [
            'token_id' => 'required'
        ]);
        
        if ($validator->fails()) {
            
            return ApiResponse::validation($validator);
        }

        try {

            $token = OauthAccessToken::findOrFail($input['token_id']);

            $token->revoked = 1;
            
            $token->delete();

            return ApiResponse::success([
                'message' => 'Token revoked successfully.'
            ]);

        } catch (\Exception $e) {
            return ApiResponse::errorGeneral('No results found.');
        }
    }

    /**
     * Get Open API tokens list
     *  @return Response
     */

    public function getTokenList() 
    {   
        $loggedInUser = Auth::user();

        $companyId = $loggedInUser->company_id;

        $openAPIUser = $this->userRepo->getOpenAPIUser($companyId);

        

        return ApiResponse::success([
            'tokens' => $this->getTokens()
        ]);
    }

    public function getTokens() 
    {
        $loggedInUser = Auth::user();

        $companyId = $loggedInUser->company_id;

        $openAPIUser = $this->userRepo->getOpenAPIUser($companyId);

        

        return ( !$openAPIUser) ? [] : $openAPIUser->tokens()->get()->toArray();
    }
}
