<?php

namespace App\Services\CompanyCam;

use App\Models\Job;
use App\Models\JobMeta;
use App\Models\CompanyCamClient;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\AuthorizationException;
use App\Services\Resources\ResourceServices;
use App\Services\CompanyCam\CompanyCamConnect;
use App\Exceptions\CompanyCam\TimeoutException;

class CompanyCamService
{

    protected $scope;
    protected $resourcesService;

    function __construct(Context $scope, ResourceServices $resourcesService)
    {
        $this->scope = $scope;
        $this->resourcesService = $resourcesService;
    }

    /**
     * Connect company cam account
     * @param  string $token | Company cam Access token
     * @param  [type] $username | Company cam username
     * @return companycamclient $object
     */
    public function connect($token)
    {
        DB::beginTransaction();
        try {
            $client = $this->getRequestObject($token);

            $user = $client->authenticate();

            $companyId = $this->scope->id();

            $companyCamClient = CompanyCamClient::whereCompanyId($companyId)->first();

            if (!$companyCamClient) {
                $companyCamClient = new CompanyCamClient(['company_id' => $companyId]);
            }

            $companyCamClient->username = $user['email_address'];
            $companyCamClient->token = $token;
            $companyCamClient->save();
        } catch (\Exception $e) {
            DB::rollback();

            throw $e;
        }
        DB::commit();

        return $companyCamClient;
    }

    /**
     * Disconnect account
     * @return boolean
     */
    public function disconnect()
    {
        $companyId = $this->scope->id();

        CompanyCamClient::whereCompanyId($companyId)->delete();

        return true;
    }

    /**
     * Create Project
     * @param  Int $jobId | Job Id
     * @return object
     */
    public function createOrUpdateProject($jobId)
    {
        try {
            $request = $this->getRequestObject();

            $job = Job::find($jobId);

            if (!$job) {
                return;
            }

            $customer = $job->customer;

            $projectName = $customer->full_name.' / '.$job->present()->jobIdReplace;
            $name = (strlen($projectName) > 255) ? substr($projectName,0,252).'...' : $projectName;

            $jobAddress = $job->address;

            if ($job->isProject()) {
                $parentJob = $job->parentJob;
                $jobAddress = $parentJob->address;
            }

            $address = [];
            $coordinates = [];

            if ($jobAddress) {
                $address = [
                    'street_address_1' => $jobAddress->address,
                    'street_address_2' => $jobAddress->address_line_1,
                    'city' => $jobAddress->city,
                    'state' => isset($jobAddress->state->code) ? $jobAddress->state->code : '',
                    'postal_code' => $jobAddress->zip,
                    'country' => isset($jobAddress->country->code) ? $jobAddress->country->code : '',
                ];

                $coordinates = [
                    'lat' => $jobAddress->lat,
                    'lon' => $jobAddress->long,
                ];
            }

            //update if already exists..
            $projectId = $job->getMetaByKey(JobMeta::COMPANY_CAM_ID);
            if ($projectId) {
                $project = $request->updateProject($projectId, $name, $address, $coordinates);
            } else {
                $project = $request->createProject($name, $address, $coordinates);

                // link project id with job..
                $job->saveMeta(JobMeta::COMPANY_CAM_ID, $project['id']);
            }

            return $project;
        } catch (\Exception $e) {
            if (in_array($e->getCode(), [400, 422])) {
                Log::error("CompanyCam - Create Project (JobId - $jobId): " . getErrorDetail($e));
            } elseif (in_array($e->getCode(), [401, 402, 403])) {
                Log::warning("CompanyCam - Create Project: " . getErrorDetail($e));
                // $this->inActivateAccount($e->getMessage());
            } elseif($e->getCode() == 504){
                    throw new TimeoutException("Request Timeout. Please try again");
            } else {
                Log::warning("CompanyCam - Create Project: " . getErrorDetail($e));
            }

            throw $e;
        }
    }

    /**
     * Get Project Photots
     * @param  string $projectId | Project Id
     * @return collection
     */
    public function getProjectPhotos($projectId, $filters = [])
    {
        try {
            $request = $this->getRequestObject();

            return $request->getProjectPhotos($projectId, $filters);
        } catch (\Exception $e) {
            // if (in_array($e->getCode(), [401, 402, 403])) {
            // 	$this->inActivateAccount($e->getMessage());
            // }
            if ($e->getCode()==504){
                throw new TimeoutException("Request Timeout. Please try again");
            }

            throw $e;
        }
    }

    /**
     * Get all projects
     * @param  array $filters | Filters array
     * @return array
     */
    public function getAllProjects($filters = [])
    {
        try {
            $request = $this->getRequestObject();

            return $request->listProjects($filters);
        } catch (\Exception $e) {
            // if (in_array($e->getCode(), [401, 402, 403])) {
            // 	$this->inActivateAccount($e->getMessage());
            // }

            throw $e;
        }
    }

    /**
     * Get Project By Id
     * @param  string $projectId | Project Id
     * @return array
     */
    public function getProjectById($projectId)
    {
        try {
            $request = $this->getRequestObject();

            return $request->getSingleProject($projectId);
        } catch (\Exception $e) {
            // if (in_array($e->getCode(), [401, 402, 403])) {
            // 	$this->inActivateAccount($e->getMessage());
            // }

            throw $e;
        }
    }

    /**
     * Get all photos
     * @param  array $filters | filters array
     * @return array
     */
    public function getAllPhotos($filters = [])
    {
        try {
            $request = $this->getRequestObject();

            return $request->companyPhotos($filters);
        } catch (\Exception $e) {
            // if (in_array($e->getCode(), [401, 402, 403])) {
            // 	$this->inActivateAccount($e->getMessage());
            // }

            throw $e;
        }
    }

    /**
     * Save photo
     * @param  int $photoId | Company cam photo id
     * @param  int $saveTo | Parent dir id (Resources)
     * @return object
     */
    public function savePhoto($photoId, $saveTo)
    {
        try {
            // get photo object..
            $request = $this->getRequestObject();
            $photo = $request->getPhoto($photoId);

            $photoUrl = null;

            // extract photo url
            foreach ($photo['uris'] as $key => $uri) {
                if ($uri['type'] == 'original_annotation') {
                    $photoUrl = $uri['uri'];
                    break;
                }
            }

            if (!$photoUrl) {
                return false;
            }

            // get image contents..
            $imageContent = file_get_contents($photoUrl);

            $name = 'cc_' . timestamp() . '_' . uniqid() . '.jpg';

            $mimeType = 'image/jpeg';

            // save to resources..
            return $this->resourcesService->createFileFromContents(
                $saveTo, // parent dir id
                $imageContent,
                $name,
                $mimeType
            );
        } catch (\Exception $e) {
            // if (in_array($e->getCode(), [401, 402, 403])) {
            // 	$this->inActivateAccount($e->getMessage());
            // }

            throw $e;
        }
    }

    /**
	 * Save photo
	 * @param  int $photoId | Company cam photo id
	 * @param  int $saveTo  | Parent dir id (Resources)
	 * @return object
	 */
	public function getPhotoUrl($photoId)
	{
		try {
			// get photo object..
			$request = $this->getRequestObject();
			$photo = $request->getPhoto($photoId);

			$photoUrl = null;

			// extract photo url
			foreach ($photo['uris'] as $key => $uri) {
				if($uri['type'] == 'original_annotation') {

					$photoUrl = $uri['uri'];
					break;
				}
			}

			return $photoUrl;

		} catch (\Exception $e) {
			// if (in_array($e->getCode(), [401, 402, 403])) {
			// 	$this->inActivateAccount($e->getMessage());
			// }

			throw $e;
		}
	}

    /****************** Private section *****************/

    private function getRequestObject($token = null)
    {
        if (empty($token)) {
            $companyCamClient = CompanyCamClient::whereCompanyId($this->scope->id())
                ->whereStatus(true)
                ->first();

            $this->companyCamClient = $companyCamClient;

            if (!$companyCamClient) {
                throw new AuthorizationException("CompanyCam account not connected", 1);
            }
            $token = $companyCamClient->token;
            // $username = $companyCamClient->username;
        }

        $request = new CompanyCamConnect($token);

        return $request;
    }

    private function inActivateAccount($error)
    {
        $companyCamClient = $this->companyCamClient;

        if ($companyCamClient) {
            $companyCamClient->status = false;
            $companyCamClient->error = $error;
            $companyCamClient->save();
        }
    }
}
