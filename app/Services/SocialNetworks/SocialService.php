<?php

namespace App\Services\SocialNetworks;

use App\Exceptions\InvalidAttachmentException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\NetworkNotExistException;
use App\Exceptions\VerifySocialTokenException;
use App\Models\CompanyNetwork as CompanyNetworkAlias;
use App\Models\CompanyNetwork;
use App\Models\NetworkMeta;
use App\Repositories\CompanyNetworksRepository;
use App\Repositories\EstimationsRepository;
use App\Repositories\ProposalsRepository;
use App\Repositories\ResourcesRepository;
use FlySystem;
use Settings;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Services\LinkedIn\LinkedIn;
use GuzzleHttp\Client;
use App\Exceptions\LinkedInException;
use App\Exceptions\SocialNetworkException;
use Hybridauth\Exception\HttpRequestFailedException;
use Hybridauth\Exception\RuntimeException;

class SocialService extends Adapter
{

    protected $adapter;

    public $facebookAPIURL = "https://graph.facebook.com/v4.0/";

    public static $error;

    public static $success;

    public function __construct(
        Adapter $adapter,
        CompanyNetworksRepository $repo,
        ResourcesRepository $resourcesRepo,
        ProposalsRepository $proposalsRepo,
        EstimationsRepository $estimateRepo
    ) {
        $this->adapter = $adapter;
        $this->repo = $repo;
        $this->resourcesRepo = $resourcesRepo;
        $this->proposalsRepo = $proposalsRepo;
        $this->estimateRepo = $estimateRepo;
    }

    /**
     * ******
     * @param  [type] $message     [status message]
     * @param  [type] $network     [network name ]
     * @param  array $attachments [type and value]
     * @return [type]              [true]
     */
    public function postOnNetworks($message, $network, $attachments = [])
    {
        $networks = explode('|', $network);
        $images = $this->getAttachementFiles($attachments);
        foreach ($networks as $network) {
            $postResponse[] = $this->{$network . 'Post'}($message, $images);
        }
        return true;
    }

    /**
     *
     * Get Page list of facebook network current login company
     * @return   [<page list>]
     **/
    public function getPageList()
    {
        $companyNetwork = $this->repo->token(CompanyNetwork::FACEBOOK);
        if (empty($companyNetwork)) {
            throw new NetworkNotExistException("Company not connected to facebook network.");
        }
        $this->facebook = $this->setAdapter($companyNetwork->token, 'facebook');
        $appsecretProof = hash_hmac('sha256', 'access_token', config('hybridauth.providers.Facebook.keys.secret'));
        $response =  $this->facebook->apiRequest($this->facebookAPIURL.'me/accounts?fields=name,picture,access_token,category,id', $appsecretProof, 'GET');

        return json_decode(json_encode($response), true);
    }

    /**
     * ******
     * method for saving pages
     * @param  [pageIds]     [array of page ids]
     * @return [boolean]     [true or false]
     */
    public function savePages($pageIds = [])
    {
        $companyNetwork = $this->repo->token(CompanyNetwork::FACEBOOK);

        if (empty($companyNetwork)) {
            throw new NetworkNotExistException("Company not connected to facebook network.");
        }
        $facebook = $this->setAdapter($companyNetwork->token, 'facebook');
        foreach ($pageIds as $pageId) {
            try {
                $pages[] = $facebook->apiRequest($this->facebookAPIURL.$pageId . '?fields=name,picture,access_token,category,id', 'Get');
            } catch(HttpRequestFailedException $e) {
                $response = json_decode($facebook->getHttpClient()->getResponseBody());
                $err = $response->error;

                if(isset($err->message)) {
					$msg = $err->message;
				}

				throw new SocialNetworkException($msg);
            }
        }
        if (!empty($pages)) {
            $networkMeta = $companyNetwork->networkMeta ?: new NetworkMeta;
            $networkMeta->meta_value = $pages;
            $networkMeta->meta_key = CompanyNetwork::PAGES;
            $companyNetwork->networkMeta()->save($networkMeta);
            return true;
        }
        return false;
    }


    /**
     * *******
     * @param  [type] $message    [description]
     * @param  array $imagePaths [array of image paths]
     * @return [boolean]          [true or false]
     */
    public function linkedinPost($message, $imagePaths = [])
    {
        $companyNetwork = $this->repo->findBy('network', 'linkedin', ['token', 'network']);
        if (!$companyNetwork) {
            return false;
        }

        try {
            $li = new LinkedIn(config('linkedin'));
            $li->setAccessToken($companyNetwork->token['client_token']);
            $statusContent = $this->setLinkedinStatus($message, $imagePaths, $li);
            $li->post('/ugcPosts', $statusContent);
            $this->setSuccessValue('linkedin');
        } catch (RuntimeException $e) {
            $message = substr($e->getMessage(), 0, strpos($e->getMessage(), "Raw Response"));

            if(strpos($message, 'Content is a duplicate')) {

                throw new LinkedInException(trans('response.error.linkedin_duplicate_text'));
	    	}elseif(strpos($message, 'exceeded the maximum allowed (1300 characters)')) {

                throw new LinkedInException(trans('response.error.linkedin_text_limit_exceeded'));
            }

            $this->setErrorkey('linkedin', [$message]);
            if (strpos($e->getMessage(), '[status] => 401') !== false) {
                $this->repo->delete('network', 'linkedin');
            }
            return false;
        }
        return true;
    }

    /**
     * *******
     * @param  [type] $message    [description]
     * @param  array $imagePaths [array of image paths]
     * @return [boolean]          [true or false]
     */
    public function facebookPost($message, $imagePaths = [])
    {
        $companyNetwork = $this->repo->findBy('network', 'facebook', ['token']);
        if (!$companyNetwork) {
            return false;
        }

        $this->facebook = $this->adapter->facebook($companyNetwork->token);
        if ($companyNetwork->networkMeta) {
            $uploadStatus = $this->postOnFacebookPages($companyNetwork, $message, $imagePaths);
            $this->setSuccessValue('facebook');

            return true;
        }

        return false;
    }

    /**
     * *******
     * @param  [type] $message    [description]
     * @param  array $imagePaths [array of image paths]
     * @return [boolean]          [true or false]
     */

    public function twitterPost($message, $imagePaths = [])
    {
        $companyNetwork = $this->repo->findBy('network', 'twitter', ['token']);
        if (!$companyNetwork) {
            return false;
        }
        $this->twitter = $this->adapter->twitter($companyNetwork->token);
        $response = $this->postOnTwitter($message, $imagePaths);
        if (isset($response->errors) && ($response->errors[0]->code == 89)) {
            $this->repo->delete('network', 'twitter');
            $this->adapter->logout();
        }
        if (isset($response->errors[0]->message)) {
            $this->setErrorkey('twitter', [$response->errors[0]->message]);
            return false;
        }
        $this->setSuccessValue('twitter');
        return true;
    }

    /**
     * *******
     * @return [value]          [token]
     */
    public function getLinkedinAccessToken()
    {
        $li = new LinkedIn(config('linkedin'));
        $token['client_token'] = $li->getAccessToken($_REQUEST['code']);
        return $token;
    }


    /**
     * **
     * @param  [type] $token   [token]
     * @param  [type] $network [network name]
     * @return [type]          [long lived token]
     */
    public function extendToken($token, $network)
    {
        return $this->{'extend' . ucfirst($network) . 'Token'}($token);
    }


    /**
     * **
     * @param  [type] $token [short lived token]
     * @return [value]       [long lieved token]
     */
    public function extendFacebookToken($token)
    {
        try {
            $fb = $this->adapter->facebook($token);
            $fb->apiRequest($this->facebookAPIURL."me?fields=id", "Get");
            $fb->setAccessToken($token);
            $extendedToken['client_token'] = $token['client_token'];

            return $extendedToken;
        } catch (\Exception $e) {
            throw new VerifySocialTokenException($e->getMessage());
        }

        return $extendedToken;
    }

    /**
     * **
     * @param  [type] $token [short lived token]
     * @return [value]       [long lieved token]
     */
    public function extendLinkedinToken($token)
    {
        $li = new LinkedIn(config('linkedin'));
        $li->setAccessToken($token['client_token']);
        try {
            $li->get('/me');
            return $token;
        } catch (\Exception $e) {
            throw new VerifySocialTokenException("Invalid token.");
        }
    }


    /**
     * **
     * check token and extend token expire time
     * @param  [type] $token [short lived token]
     * @return [value]       [long lieved token]
     */

    public function extendTwitterToken($token)
    {
        try{
            $twitter = $this->adapter->twitter($token);
            $twitter->apiRequest('/account/update_profile.json', 'POST');;
            if ($twitter->api()->http_code != 200) {
                throw new VerifySocialTokenException("Invalid token.");
            }

            return $token;
        } catch(HttpRequestFailedException $e){

            throw new SocialNetworkException($e->getMessage());
        }
    }

    /**
     * ***
     * @return [url] [login url of linkedin]
     */
    public function getLinkedinLoginUrl()
    {
        $li = new LinkedIn(config('linkedin'));
        $companyId = $this->repo->companyId();
        $url = $li->getLoginUrl(
            [
                LinkedIn::SCOPE_BASIC_PROFILE,
                LinkedIn::SCOPE_W_MEMBER_SOCIAL
            ],
            $companyId
        );
        return $url;
    }


    /************************ Private section  ***********************************/


    /**
     * Get Attachments files array
     * @param $attachments Array | Array of attachments; type (Resource or Proposal) and values
     * @return Array
     * @access private
     */
    private function getAttachementFiles(array $attachments = [])
    {
        $files = [];
        if (empty($attachments)) {
            return $files;
        }
        foreach ($attachments as $attachment) {
            if (!ine($attachment, 'type') || !ine($attachment, 'value')) {
                throw new InvalidAttachmentException("Invalid Attachment.");
            }
            $files[] = $this->getFile($attachment['type'], $attachment['value']);
        }
        return $files;
    }

    /**
     * Get File path for attchment
     * @param $type String | type of attachment file (e.g., resource or proposal)
     * @param $id Int or String | id of resource or proposal
     * @return String (path of file)
     * @access private
     */
    private function getFile($type, $id)
    {
        try {
            if ($type == 'resource' || $type == 'upload') {
                $image = $this->resourcesRepo->getFile($id);
                $filePath = config('resources.BASE_PATH') . $image->path;
                $imageMimeType = $image->mime_type;
            } elseif ($type == 'proposal') {
                $image = $this->proposalsRepo->getById($id);
                $filePath = config('jp.BASE_PATH') . $image->file_path;
                $imageMimeType = $image->file_mime_type;
            } elseif ($type == 'estimate') {
                $image = $this->estimateRepo->getById($id);
                $imageMimeType = $image->file_mime_type;
                $filePath = config('jp.BASE_PATH') . $image->file_path;
            } else {
                goto Invalid;
            }
            if (!in_array($imageMimeType, config('resources.image_types'))) {
                throw new InvalidFileException("Invalid File Type");
            }
            return $filePath;
        } catch (\Exception $e) {
            Log::error('SocialService : ' . $e->getMessage() . ' ' . $e->getLine() . ' ' . $e->getFile());
            Invalid :
            throw new InvalidAttachmentException("Post share failed. Invalid Attachment.");
        }
    }

    /**
     *
     * @param [message] $message    [message]
     * @param [imagePaths] $imagePaths [Image paths]
     * @return complete content
     */
    private function setLinkedinStatus($message, $imagePaths = [], $li)
    {
        $image = [];
        $userProfile = $li->get('/me');
        $urnNumber = $userProfile['id'];
        $content = [
			'author' => 'urn:li:person:'.$urnNumber,
			'lifecycleState' => 'PUBLISHED',
			'specificContent' => [
				'com.linkedin.ugc.ShareContent' => [
					'shareCommentary' => [
						"text" => $message
                    ],
					'shareMediaCategory' => "NONE"
                ]
            ]
        ];

        $websiteLink = Settings::get('WEBSITE_LINK');
        if ($websiteLink == '#') {
            $websiteLink = config('jp.site_url');
        }
        $user = Auth::user();
        $content['visibility'] = [
            'com.linkedin.ugc.MemberNetworkVisibility' =>'PUBLIC'
        ];

        if (empty($imagePaths)) {
            return $content;
        }

        $registerImage = [
            "registerUploadRequest" => [
                "recipes" => [
		            "urn:li:digitalmediaRecipe:feedshare-image"
		        ],
		        "owner" => "urn:li:person:".$urnNumber,
		        "serviceRelationships" => [
		            [
		                "relationshipType" => "OWNER",
		                "identifier" => "urn:li:userGeneratedContent"
		            ]
		        ]
		    ]
        ];

		$token = $li->getAccessToken();
		$li->setAccessToken($token.'&action=registerUpload');
		$response = $li->post('/assets', $registerImage);

		$li->setAccessToken($token);

		$uploaded_url = $response['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];

		$imageData = [
			'headers' => [
				'Authorization' => 'Bearer ' . $token
			],
			'body' => FlySystem::read(reset($imagePaths)),
		];

		$guzzleClient = new Client;
		$result = $guzzleClient->post($uploaded_url, $imageData);

		$asset = $response['value']['asset'];
		$content["specificContent"]["com.linkedin.ugc.ShareContent"]["shareMediaCategory"] = "IMAGE";
		$content["specificContent"]["com.linkedin.ugc.ShareContent"]["media"][] = [
			"status" => "READY",
			"media" => $asset,
        ];

        return $content;
    }

    /**
     *
     * @param [message] $message    [message]
     * @param [imagePath] $imagePaths [Image paths]
     * @return [boolean] [true or false]
     */
    private function postOnTwitter($message, $imagePaths = array())
    {
		try {
			if (empty($imagePaths)) {

                return $this->twitter->apiRequest('statuses/update.json','POST',array( 'status' => substr( $message, 0, 140)) );
			}

            $mediaIds = [];
            foreach ($imagePaths as $key => $image) {
				$mediaContent = FlySystem::read($image);;
				$parameters = [
					'media_data' => base64_encode($mediaContent),
                ];

			    $media = $this->twitter->apiRequest( 'https://upload.twitter.com/1.1/media/upload.json', 'POST', $parameters);

                if($media)  {
                    $mediaIds[] = $media->media_id_string;
                }
            }

			$parameters = [
				'status' => substr($message, 0, 117),
            ];

            if(!empty($mediaIds)){
                $parameters['media_ids'] = implode(',', $mediaIds);
            }

		    return $this->twitter->apiRequest('statuses/update.json', 'POST', $parameters);
		} catch(HttpRequestFailedException $e) {
			$response = json_decode($this->twitter->getHttpClient()->getResponseBody());
			switch ($response->errors[0]->code) {
				case 187:
					throw new SocialNetworkException("This seems to be a duplicate text. If you still want to publish, please try with an image.");
				break;
				default :
					throw new SocialNetworkException($response->errors[0]->message);
				break;
			}
		}
    }

    /**
     * *****
     * @param [token] $token   [token]
     * @param [network] $network [network name]
     */
    private function setAdapter($token, $network)
    {
        return $this->adapter->{$network}($token);
    }

    /**
     * ********
     * @param  [object] $companyNetwork [company network object]
     * @param  [type] $message        [message]
     * @param  [array] $imagePaths     [array of image paths]
     * @return [type]                 [description]
     */
    private function postOnFacebookPages($companyNetwork, $message, $imagePaths)
    {
        $pages = $companyNetwork->networkMeta->meta_value;
        $error = false;
        $content['message'] = $message;

        if(!empty($imagePaths)) {
            $url = FlySystem::getUrl(reset($imagePaths));
            $content['url'] = str_replace(' ', '%20', $url);
        }

        foreach ($pages as $key => $page) {

            try {
                $headers = [
                    'Authorization' => 'Bearer ' . $page['access_token'],
                ];
                  // Refresh proof for API call.
		        $parameters = $content + [
	                'appsecret_proof' => hash_hmac('sha256', $page['access_token'], config('hybridauth.providers.Facebook.keys.secret')),
	            ];

				if (empty($imagePaths)) {
			        $response = $this->facebook->apiRequest($this->facebookAPIURL.$page['id'].'/feed', 'POST', $parameters, $headers);
				}else{
			 		$response =	$this->facebook->apiRequest($this->facebookAPIURL.$page['id'].'/photos', "POST", $parameters, $headers);
				}
            } catch(HttpRequestFailedException $e) {
                $response = json_decode($this->facebook->getHttpClient()->getResponseBody());

				$err = $response->error;

                if(isset($err->message)) {
					$msg = $err->message;
                }

                throw new SocialNetworkException($msg);
            }
        }
        if (empty($pages)) {
            $companyNetwork->networkMeta()->delete();
            return false;
        }
        if ($error) {
            $networkMeta = $companyNetwork->networkMeta;
            $networkMeta->meta_value = $pages;
            $companyNetwork->networkMeta()->save($networkMeta);
            return false;
        }
        return true;
    }

    /**
     * ***
     * @param [string] $key   [network key]
     * @param [array] $value [error message]
     */
    private function setErrorkey($key, $value)
    {
        self::$error[$key] = $value;
    }

    /**
     * ***
     * @param [name] $key   [network name]
     */
    private function setSuccessValue($name)
    {
        self::$success[] = $name;
    }
}
