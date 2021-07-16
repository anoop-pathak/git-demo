<?php

namespace App\Services\Hover;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\CookieJar;
use App\Repositories\HoverRepository;
use App\Repositories\HoverJobRepository;
use App\Models\HoverJob;
use App\Models\HoverReport;
use App\Models\HoverImage;
use App\Models\Measurement;
use App\Services\Measurement\MeasurementService;
use Crypt;
use App;
use Exception;
use App\Repositories\MeasurementRepository;
use GuzzleHttp\Exception\RequestException;
use App\Models\HoverClient;
use FlySystem;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\AccountNotConnectedException;
use App\Models\Job;
use App\Exceptions\AccountAlreadyConnectedException;
use App\Models\HoverUser;
use App\Exceptions\Hover\HoverUserNotExistException;
use HoverCaptureRequest;
use App\Exceptions\Hover\HoverJobExistException;
use App\Services\Resources\ResourceServices;
use App\Models\Country;
use App\Models\State;

class HoverService {
 	protected $repo;
	protected $request;
	protected $sandboxRequest;
	protected $sandboxRequestV2;
	protected $measurementService;
	protected $measurementRepo;
	protected $client;
	protected $hoverClient;
 	public function __construct(HoverRepository $repo,
		MeasurementService $measurementService,
		MeasurementRepository $measurementRepo,
		HoverJobRepository $hoverJobRepo,
		ResourceServices $resourcesService
	){
 		$this->repo = $repo;
		$this->request = new Client(['base_uri' =>  config('hover.base_url')]);
		$this->sandboxRequest = new Client(['base_uri' =>  config('hover.sandbox_base_url')]);
		$this->sandboxRequestV2 = new Client(['base_uri' =>  config('hover.sandbox_base_url_v2')]);
		$this->measurementService = $measurementService;
		$this->measurementRepo    = $measurementRepo;
		$this->hoverJobRepo       = $hoverJobRepo;
		$this->resourcesService   = $resourcesService;
	}
 	public function authentication()
	{
		$this->disconnect();
 		$state = [
			'company_id' => getScopeId(),
			'user_id'    => \Auth::id()
		];
 		$data = [
			'client_id'     => config('hover.client_id'),
			'response_type' => 'code',
			'redirect_uri'  => config('hover.redirect_url').'?'.http_build_query($state),
		];
 		$url = config('hover.sandbox_base_url').'oauth/authorize'.'?'.http_build_query($data);
 		return $url;
	}
 	/**
	* get access token and save data to hover client
	*
	* @param $code, $userId and $companyId
	* @return hover client data
	*/
 	public function saveTokenAndWebhook($code, $userId, $companyId, $hoverAuth = false)
	{
		$hoverClient = HoverClient::whereNotNull('access_token')
				->whereNotNull('refresh_token')
				->where('company_id', $companyId)
				->first();
		if($hoverClient) {
			throw new AccountAlreadyConnectedException("Account already connected.");
		}

		$request = new Client;

		$postfix = '';
		if(!$hoverAuth){
			$state = [
				'company_id' => $companyId,
				'user_id'    => $userId
			];
			$postfix = '?'.http_build_query($state);
		}
		$data = [
			'client_id'     => config('hover.client_id'),
			'client_secret' => config('hover.client_secret'),
			'code'			=> $code,
			'redirect_uri'  => config('hover.redirect_url').$postfix,
			'grant_type'	=> 'authorization_code',
 		];

 		$response = $request->request('POST', config('hover.sandbox_base_url').'oauth/token', [
			'json' => $data
		]);
		$data = json_decode($response->getBody(), 1);
 		$webhook = $this->createWebhook($data['access_token']);
		$data['webhook_id'] = $webhook['id'];
 		$this->repo->saveHoverClient($data['owner_id'],
			$data['owner_type'],
			$userId,
			$companyId,
			$data
		);
	}
 	/**
	* create webhook
	*
	* @return json response
	*/
	public function createWebhook($accessToken)
	{
 		$weebhook = [
			'webhook' => [
				'url' => config('hover.webhook_url'),
				'content_type' => 'json'
			],
		];
 		$response = $this->sandboxRequestV2->request('POST', 'webhooks', [
			'json' => $weebhook,
			'headers' => [
				'Authorization' => "Bearer {$accessToken}",
			]
		]);
 		return json_decode($response->getBody(), 1);
	}
 	/**
	* verify webhook
	*
	* @param $code
	* @return success response
	*/
	public function verifyWebhook($code, $webhookId)
	{
		$this->sandboxRequestV2->request('PUT', 'webhooks/'.$code.'/verify');
 		HoverClient::where('webhook_id', $webhookId)
			->update(['webhook_verified' => true]);

	}
 	public function jobSync($companyId, $customerId, $jobId)
	{
		if(!$companyId) return false;
		if(!($jobId || $customerId)) return false;

		setScopeId($companyId);
		$jobs = Job::query();

        if($jobId) {
            $jobs->where(function($query) use($jobId){
                $query->whereIn('id', (array)$jobId)
                      ->orWhereIn('parent_id', (array)$jobId);
            });
        }
         if($customerId) {
            $jobs->where('customer_id', $customerId);
        }
         $jobs = $jobs->where('company_id', $companyId)->where('sync_on_hover', true)->get();
         foreach ($jobs as $job) {
            $hoverJob = $this->createOrUpdateJob($job);
            if(!$hoverJob) continue;
			$this->jobCreateOnHover($hoverJob);
        }
	}
 	public function fullJobSync($job, $inputs = array())
	{
		if(!$job) return false;

		$this->hoverClient = $this->getHoverClient();
 		$hoverJob = $job->hoverJob;
 		if($hoverJob && ($hoverJob->state == HoverJob::COMPLETE)) return $hoverJob;

 		if(isset($inputs['hover_user_id'])) {
			$job->hover_user_id = $inputs['hover_user_id'];
		}
		if(isset($inputs['hover_deliverable_id'])) {
			$job->hover_deliverable_id = $inputs['hover_deliverable_id'];
		}

		if(isset($inputs['hover_deliverable_id']) || isset($inputs['hover_user_id'])) {
			$job->save();
		}

 		if($hoverJob && ($hoverJob->owner_id != $this->hoverClient->owner_id)) {
			$hoverJob->measurement()->delete();
			$hoverJob->delete();
			$hoverJob->hoverReport()->delete();
			$hoverJob->hoverImage()->delete();
			$hoverJob = null;
		}

		if(!$hoverJob) {
			$hoverJob = HoverJob::where('owner_id', $this->hoverClient->owner_id)
					->where('job_id', $job->id)
					->withTrashed()
					->first();
			if($hoverJob) {
				$hoverJob->restore();
			}
		}
		$hoverJob = $this->createOrUpdateJob($job);
		$this->jobCreateOnHover($hoverJob);
		$this->createMeasurement($hoverJob);
 		return $hoverJob;
	}
 	/**
	* get single hover job
	*
	* @param $jobId
	*/
	public function jobStateManage($hoverJobId, $state, $webhookId)
	{
		$hoverClient = $this->repo->getByWebhookId($webhookId);
		if(!$hoverClient) return false;
 		$hoverJob = HoverJob::where('hover_job_id', $hoverJobId)
							->whereCompanyId($hoverClient->company_id)
							->first();
 		if(!$hoverJob) return false;
		setScopeId($hoverClient->company_id);
		if($state == 'first_attempt') {
			$state = HoverJob::UPGRADING;
			$this->updateDeliverable($hoverJobId);
		}

		if($hoverJob->deliverable_id == 4 && $state == 'waiting_approval') {
			$hoverJob->state = HoverJob::COMPLETE;
			$hoverJob->save();
		} else {
			// job state saving
			$hoverJob->state = $state;
			$hoverJob->save();
			$this->createMeasurement($hoverJob);
		}
	}

	public function captureRequestWithSynced($job, $input)
	{
		$hoverJob = $this->captureRequest($job, $input);
		$this->captureRequestOnHover($hoverJob);

		return $hoverJob;
	}
 	public function createMeasurement($hoverJob)
	{

		$this->hoverClient = $this->getHoverClient();
		$headers = [
			'headers' =>[
				'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];

 		$measurement = $hoverJob->measurement;
		if(!$measurement) {
			return;
		}

		if(in_array($hoverJob->state, [HoverJob::PROCESSING_UPLOAD])) return false;

 		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJob->hover_job_id, $headers);
		$imagesResponse = json_decode($response->getBody(), 1);
		$imageIds = isset($imagesResponse['images']) ? $imagesResponse['images'] : [] ;
 		// save hover images
		if(!empty($imageIds)) {
			$this->getImages($hoverJob, $imageIds, $headers);
		}
 		if($hoverJob->state != HoverJob::COMPLETE) return false;
 		$this->reportPdf($hoverJob, $measurement, $headers);
		$this->reportJson($hoverJob, $measurement, $headers);
		$this->reportXML($hoverJob, $measurement, $headers);
		$this->reportXLSX($hoverJob, $measurement, $headers);
	}
 	/**
	* disconnect webhook
	*/
	public function disconnect()
	{
		$hoverClient = $this->repo->getHoverClient();
		if(!$hoverClient) return false;
		$hoverClient->delete();
 		$webhookId = $hoverClient->webhook_id;
		if(!$webhookId) return false;
 		$response = $this->sandboxRequestV2->request("DELETE", 'webhooks/'.$webhookId, [
	 		'headers' => [
				'Authorization' => "Bearer {$hoverClient->access_token}",
			]
		]);
 		return json_decode($response->getBody(), 1);
	}

	public function getUsers($filters)
	{
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
		$queryString = [];
		$url = 'users';
		$queryString['per'] = 20;

		if(ine($filters, 'limit')) {
			$queryString['per'] = $filters['limit'];
		}
 		if(ine($filters, 'page')) {
			$queryString['page'] = $filters['page'];
		}
 		if(ine($filters, 'search')) {
			$queryString['search'] = $filters['search'];
		}
 		if(!empty($queryString)) {
			$url .= '?'.http_build_query($queryString);
		}
		$response = $this->sandboxRequestV2->request('GET', $url, $headers);
		$responseData = $response->getBody();
		$jsonResponse = json_decode($response->getBody(), 1);

		return $jsonResponse;
	}

	public function reAssingUser($hoverJob)
	{
		$this->hoverClient = $this->getHoverClient();

		$jobData = [
			'headers' =>[
				'Authorization' => "Bearer {$this->hoverClient->access_token}",
			],
			'json' =>[
				'user_id'      => $hoverJob->hover_user_id,
				'access_token' => $hoverJob->customer_name,
			]
		];

 		$response = $this->sandboxRequestV2->request('POST', "jobs/{$hoverJob->hover_job_id}/reassign", $jobData);
 		$jsonResponse = json_decode($response->getBody(), 1);
 		return $jobData;
	}

	/**
	 * Share Hover Job
	 */
	public function shareHoverJob($job)
	{
		$hoverJob = $job->hoverJob;
		if(!$hoverJob) return [];
		if($hoverJob->state != 'complete') return [];
		$this->hoverClient = $this->getHoverClient();
		$data = [
			'headers' =>[
				'Authorization' => "Bearer {$this->hoverClient->access_token}",
			],
			'job_share' => [
				'job_id' => $hoverJob->hover_job_id,
			],
			'current_user_id' => $job->hover_user_id,
		];

		$response = $this->request->post('job_shares', [
			'json' => $data,
		]);
		$jsonResponse = $response->json();
		$url = $jsonResponse['job_share'];

		return $url;
	}

	/**
	 * Delete Capture Request
	 */
	public function deleteCaptureRequest($captureRequestId, $currentEmail)
	{
		$query = [
			'current_user_email' => $currentEmail
		];
		$this->hoverClient= $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
		$url = 'capture_requests/'.$captureRequestId.'?'.http_build_query($query);
		$response = $this->sandboxRequestV2->request("DELETE", $url, $headers);
		if($response) return true;
	}

	/**
	 * Create Capture Request
	 */
	public function captureRequest($job, $inputs = array())
	{
		if(!(ine($inputs, 'customer_email')
					&& ine($inputs, 'customer_email')
					&& ine($inputs, 'hover_user_id')
					&& ine($inputs, 'hover_user_email')
					&& ine($inputs, 'job_address'))) return false;
		$this->hoverClient = $this->repo->getHoverClient();
		$data = [
			'name'            => $job->name,
			'company_id'      => getScopeId(),
			'customer_name'   => $inputs['customer_name'],
			'customer_email'  => $inputs['customer_email'],
			'customer_phone'  => ine($inputs, 'customer_phone') ? $inputs['customer_phone'] : null,
			'location_line_1' => ine($inputs, 'job_address') ? $inputs['job_address'] : null,
			'location_line_2' => ine($inputs, 'job_address_line_2') ? $inputs['job_address_line_2'] : null,
			'location_city'   => ine($inputs, 'job_city') ? $inputs['job_city'] : null,
			'user_email'      => ine($inputs, 'hover_user_email') ? $inputs['hover_user_email'] : null,
			'hover_user_id'   => ine($inputs, 'hover_user_id') ? $inputs['hover_user_id'] : null,
			'job_id'          => $job->id,
			'location_postal_code' => ine($inputs, 'job_zip_code') ? $inputs['job_zip_code'] : null,
			'owner_id'            => $this->hoverClient->owner_id,
			'external_identifier' => $job->lead_number,
			'deliverable_id' =>  $inputs['deliverable_id'],
			'is_capture_request' => true,
		];

		if(ine($inputs, 'country_id')) {
			$country = Country::find($inputs['country_id']);
			$data['location_country'] = $country->name;
			$data['country_id'] = $country->id;
		}

		if(ine($inputs, 'state_id')) {
			$state = State::find($inputs['state_id']);
			$data['location_region'] = $state->name;
			$data['state_id'] = $state->id;
		}
		$hoverJob = HoverJob::create($data);
		$job->hover_deliverable_id = $inputs['deliverable_id'];
		$job->hover_user_id = $inputs['hover_user_id'];
		$job->sync_on_hover = true;
		$job->save();

		return $hoverJob;
	}

	/**
	 * Save photo
	 * @param  int $imageId | Hover Img id
	 * @param  int $saveTo  | Parent dir id (Resources)
	 * @return object
	 */
	public function savePhoto($imgId, $saveTo)
	{
		try {
			$hoverImage = HoverImage::where('company_id', getScopeId())->where('hover_image_id', $imgId)->firstOrFail();
			$fullPath = config('jp.BASE_PATH').$hoverImage->file_path;
			$imageContent = FlySystem::read($fullPath);
			$name = 'hover_'. $imgId .'_'.uniqid().'.jpg';
			$mimeType = 'image/jpeg';

			// save to resources..
			return $this->resourcesService->createFileFromContents(
				$saveTo, // parent dir id
				$imageContent,
				$name,
				$mimeType
			);

		} catch (Exception $e) {

			throw $e;
		}
	}

 	/**
	 * Create hover job
	 *
	 * @return Response
	 */
	private function createOrUpdateJob($job)
	{
		if(!$job) return false;
		$this->hoverClient = $this->getHoverClient();
		try {
			$hoverJob = $job->hoverJob;

			if($hoverJob && ($hoverJob->owner_id != $this->hoverClient->owner_id)) return $hoverJob;

			if($hoverJob->state == HoverJob::COMPLETE) return $hoverJob;

			if($hoverJob && $hoverJob->is_capture_request) {
				$this->updateCaptureHoverUser($job);
			} else {
				$hoverJob = $this->createHoverJobDB($job);
			}
			return $hoverJob;
		} catch(UnauthorizedException $e){
			throw new UnauthorizedException(trans('response.error.third_party_unauthorized', ['attribute' => 'Hover']));
		} catch(Exception $e) {
 			throw $e;
		}
	}
 	/**
	* get hover job images and saving in database
	*
	* @param $imageIds
	* @param hover $jobId
	*/
	private function getImages($hoverJob, $imageIds, array $headers=[])
	{
		foreach ($imageIds as $image) {
			$response = $this->sandboxRequestV2->request('GET', 'images/'.$image['id'].'/image.jpg', $headers);
			$content = $response->getBody()->getContents();
			$baseName = 'hover/hover_images/'.uniqueTimestamp().'_image.jpg';
			if($response) {
				$hoverImage = HoverImage::firstOrNew([
					'hover_job_id'   => $hoverJob->hover_job_id,
					'hover_image_id' => $image['id'],
					'company_id'     => $hoverJob->company_id,
					'job_id'         => $hoverJob->job_id,
				]);
				$hoverImage->file_path  = $baseName;
				$hoverImage->save();
			}
			$filePath = config('jp.BASE_PATH'). $baseName;
			FlySystem::write($filePath, $content);
		}
	}
 	/**
	* renew and update token
	*
	* @return json response
	*/
	private function renewToken($hoverClient)
	{
		try {
 			$data = [
				'grant_type' => 'refresh_token',
				'refresh_token' => $hoverClient->refresh_token,
			];
 			$response = $this->sandboxRequest->request('POST', 'oauth/token', [
				'json' => $data
			]);
 			$data = json_decode($response->getBody(), 1);
 			return $this->repo->updateAccessToken($hoverClient,
				$data['access_token'],
				$data['refresh_token'],
				$data['created_at'],
				$data['expires_in']
			 );
 		} catch (RequestException $e) {

			if($e->getCode() == 401) {
				throw new UnauthorizedException(trans('response.error.third_party_unauthorized', ['attribute' => 'Hover']));
			}
   			throw new Exception($e);
		} catch (Exception $e) {
			throw $e;
		}
	}
 	/**
	* hover create job parameters
	*
	* @param $job
	* @return  array of params
	*/
	private function JobCreateOnHover($hoverJob)
	{
		$this->hoverClient = $this->getHoverClient();
 		$jobData = [
			'headers'=>[
				'Authorization' => "Bearer {$this->hoverClient->access_token}",
			],
			'json' => [
				'job' => [
				'name'            => $hoverJob->name,
				'customer_name'   => $hoverJob->customer_name,
				'customer_email'  => $hoverJob->customer_email,
				'customer_phone'  => $hoverJob->customer_phone,
				'location_line_1' => $hoverJob->location_line_1,
				'location_line_2' => $hoverJob->location_line_2,
				'location_city'    => $hoverJob->location_city,
				'location_country' => $hoverJob->location_country,
				'location_region'  => $hoverJob->location_region,
				'location_postal_code' => $hoverJob->location_postal_code,
				'external_identifier'  => $hoverJob->external_identifier,
				'deliverable_id'	=> $hoverJob->deliverable_id,
			],
			'current_user_email' => $hoverJob->user_email,
			]
		];
		if((App::environment('local')) || (App::environment('staging')) || (App::environment('qa')) || (getScopeId() == 12)) {
			$jobData['json']['job']['test_state'] = 'complete';
		}
 		if($hoverJob->hover_job_id) {
			$response = $this->request->request('PUT', "jobs/{$hoverJob->hover_job_id}", $jobData);
		} else {
			$response = $this->request->request('POST', 'jobs', $jobData);
		}
 		$jsonResponse = json_decode($response->getBody(), 1);
		$hoverJobId = $jsonResponse['job']['id'];
		$hoverJob->hover_job_id = $hoverJobId;
		$hoverJob->state  = $jsonResponse['job']['state'];
		$hoverJob->save();

		if($hoverJobId) {
			$meta['hover_job_id'] = $hoverJobId;
			$title = 'hover_job_'.$hoverJobId;
			$measurement = $this->measurementRepo->save($hoverJob->job_id,
				$title,
				$values = [],
				$this->hoverClient->created_by,
				$meta
			);
		}

 		return $jobData;
	}
 	/**
	* save hover job
	*/
	private function saveReport($hoverJob, $filePath, $fileName, $fileMimeType, $fileSize)
	{
		$report = HoverReport::firstOrNew([
			'hover_job_id' 	 => $hoverJob->hover_job_id,
			'company_id'     => $hoverJob->company_id,
			'file_mime_type' => $fileMimeType,
		]);
		$report->file_path = $filePath;
		$report->file_size = $fileSize;
		$report->file_name = $fileName;
		$report->save();
 		return $report;
	}
 	/**
	* get users detail
	*
	* @return users collection
	*/
	private function getUserEmail($email = null)
	{
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];

		if($email) {
			$data['search'] = $email;
		} else {
			$data['admin'] = 'true';
		}

 		$response = $this->sandboxRequestV2->request('GET', 'users'.'?'.http_build_query($data), $headers);
		$jsonResponse = json_decode($response->getBody(), 1);
 		return $jsonResponse['results'];
	}

 	/**
	* check user exist in hover
	*/
	private function checkUserExist($email)
	{
		if(!$email) return false;
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
 		$data = [
			"search" =>	$email,
		];
 		$response = $this->sandboxRequestV2->request('GET', 'users'.'?'.http_build_query($data), $headers);
		$jsonResponse = json_decode($response->getBody(), 1);
 		return !empty($jsonResponse['results']);
	}

 	/**
	* check job exist or not
	*/
	private function checkJobExist($hoverJobId)
	{
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJobId, $headers);
 		return $response;
	}
 	// private function setRequestHeaders()
	// {
	// 	$this->hoverClient = $this->getHoverClient();
	// 	if(!$this->hoverClient) {
	// 		throw new AccountNotConnectedException("Hover Account has not connected.");;
	// 	}
 	// 	$now = \Carbon\Carbon::now()->toDateTimeString();
	// 	if(!($now < $this->hoverClient->expiry_date_time)) {
	// 		$this->hoverClient  = $this->renewToken($this->hoverClient);
	// 	}
 	// 	$this->request->setDefaultOption('headers',[
	// 		"Content-Type"  => "application/json",
	// 		'Authorization' => "Bearer {$this->hoverClient->access_token}",
	// 	]);
 	// 	$this->sandboxRequestV2->setDefaultOption('headers',[
	// 		'Authorization' => "Bearer {$this->hoverClient->access_token}",
	// 	]);
 	// 	$this->sandboxRequest->setDefaultOption('headers',[
	// 		'Authorization' => "Bearer {$this->hoverClient->access_token}",
	// 	]);
	// }
 	private function createHoverJobDB($job)
	{
		$hoverJob = HoverJob::where('job_id', $job->id)->first();
		$hoverUser = $this->getHoverUser($job->hover_user_id);

		if($customer = $job->customer) {
			$name = $customer->full_name;
			$email = $customer->email;
			$phone = $customer->phones ? $customer->phones->first()->number : null;
		}
 		if($jobAddress = $job->address) {
			$location1 = $jobAddress->address ?: null;
			$location2 = $jobAddress->address_line_1 ?: null;
			$postalCode = $jobAddress->zip ?: null;
			$city = $jobAddress->city ?: null;
			$country = $jobAddress->country ? $jobAddress->country->name : null;
			$state = $jobAddress->state ? $jobAddress->state->name : null;
		}

		$data = [
			'name'            => $name.' /'.$job->number,
			'company_id'      => getScopeId(),
			'customer_name'   => $name,
			'customer_email'  => $email,
			'customer_phone'  => $phone,
			'location_line_1' => $location1,
			'location_line_2' => $location2,
			'location_city'    => $city,
			'location_country' => $country,
			'location_region'  => $state,
			'user_email'       => $hoverUser->email,
			'hover_user_id'    => $hoverUser->hover_user_id,
			'job_id'   => $job->id,
			'location_postal_code' => $postalCode,
			'owner_id'             => $this->hoverClient->owner_id,
			'external_identifier'  => $job->lead_number,
			'deliverable_id' =>  ($job->hover_deliverable_id) ?: 4,
		];
 		if(!$hoverJob) {
			$hoverJob = HoverJob::create($data);
		} else {
			$oldHoverUserId = $hoverJob->hover_user_id;
			$hoverJob->update($data);
			if($oldHoverUserId != $hoverJob->hover_user_id) {
				$this->reAssingUser($hoverJob);
			}
		}
 		return $hoverJob;
	}

	/**
	 * Create Capture Request on Hover
	 */
	private function captureRequestOnHover($hoverJob)
	{
		if(!$hoverJob) return false;
		$this->hoverClient = $this->getHoverClient();
		try {
			$data = [
				'headers'=>[
					'Authorization' => "Bearer {$this->hoverClient->access_token}",
				],
				'capture_request' => [
					'capturing_user_name'   => $hoverJob->customer_name,
					'capturing_user_email'  => $hoverJob->customer_email,
					'capturing_user_phone'	=> $hoverJob->customer_phone,
					'job_attributes' => [
						'name'            => $hoverJob->name,
						'location_line_1' => $hoverJob->location_line_1,
						'location_line_2' => $hoverJob->location_line_2,
						'location_city'   => $hoverJob->location_city,
						'location_postal_code' => $hoverJob->location_postal_code,
						'deliverable_id'	   => $hoverJob->deliverable_id,
					],
				],
				'current_user_id' => $hoverJob->hover_user_id,
			];

			if((App::environment('dev'))
				|| (App::environment('local'))
				|| (App::environment('staging'))
				|| (App::environment('qa'))
				|| (getScopeId() == 12)) {
				$data['capture_request']['job_attributes']['test_state'] = 'complete';
			}

			$response = $this->sandboxRequestV2->post('capture_requests', [
				'json' => $data,
			]);
			$jsonResponse = $response->json();
			$hoverJob->hover_job_id = $jsonResponse['pending_job_id'];
			$hoverJob->capture_request_id = $jsonResponse['id'];
			$hoverJob->state = $jsonResponse['state'];
			$hoverJob->save();
			return $hoverJob;
		} catch(UnauthorizedException $e){
			throw new UnauthorizedException(trans('response.error.third_party_unauthorized', ['attribute' => 'Hover']));
		} catch(Exception $e) {

			throw $e;
		}
	}

	/**
	 * Get Hover Images Urls
	 */
	public function getHoverImageUrls($id)
	{
		$hoverJob = $this->hoverJobRepo->getHoverJobByHoverJobId($id);
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJob->hover_job_id, $headers);
		$imagesResponse = $response->json();

		$imageIds = isset($imagesResponse['images']) ? $imagesResponse['images'] : [] ;

		// save hover images
		/*if(!empty($imageIds)) {
			$this->getImages($hoverJob, $imageIds);
		}*/
		$imageUrl = [];
		foreach ($imageIds as $image) {
			$imageUrl[] = [
				'id' => $image['id'],
				'url' => config('hover.sandbox_base_url_v2').'images/'.$image['id'].'/image.jpg'
			];
		}

		return $imageUrl;
	}

	/**
	 * Get Hover Images Urls
	 */
	public function getJobDetail($hoverJobId)
	{
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJobId, $headers);
		$imagesResponse = $response->json();

		return $imagesResponse;
	}

	/**
	 * Get Organization Detail
	 */
	public function getOrgsDetail()
	{
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
		$response = $this->request->request('GET', 'orgs', $headers);
		$orgsResponse = $response->json();
		$organization = $orgsResponse['orgs'];

		return $organization;
	}

	/**
	* Change Deliverable Id
	*
	* @return Response
	*/
	public function changeDeliverableId($jobId, $newDeliverableId)
	{
		$hoverJob = HoverJob::where('job_id', $jobId)->where('company_id', getScopeId())->firstOrFail();
		$job = $hoverJob->job;
		$this->hoverClient = $this->getHoverClient();
		$deliverableData = [
			'headers'=>[
				'Authorization' => "Bearer {$this->hoverClient->access_token}",
			],
			'deliverable_change_request' => [
				'job_id' => $hoverJob->hover_job_id,
				'new_deliverable_id' => $newDeliverableId,
			],
			'current_user_email' => $hoverJob->user_email
		];

	    $response = $this->sandboxRequestV2->request('POST', 'deliverable_change_requests', [
			'json' => $deliverableData
	    ]);

	    $responseJson = $response->json();
		$job->hover_deliverable_id = $responseJson['new_deliverable_id'];
		$job->save();
		$hoverJob->deliverable_id = $responseJson['new_deliverable_id'];
		$hoverJob->save();

		return $responseJson;
	}

	/**
	 * Upgrate Deliverable Id In DB
	 */
	private function updateDeliverable($hoverJobId)
	{
		$hoverJob = HoverJob::where('hover_job_id', $hoverJobId)->first();

		$query = [
			'job_id' => $hoverJobId,
			'current_user_id' => $hoverJob->hover_user_id
		];
		$this->hoverClient = $this->getHoverClient();
		$headers = ['headers'=>[
			'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		];
		$response = $this->sandboxRequestV2->request('GET', 'deliverable_change_requests'.'?'.http_build_query($query), $headers);
		$jsonResponse = $response->json();
		if($jsonResponse) {
			$hoverJob->deliverable_id = $jsonResponse['results'][0]['new_deliverable_id'];
			$hoverJob->save();
		}
		return true;
	}

 	/**
	* download json report from hover
	*
	* @param $jobId
	* @return json file in response
	*/
	private function reportJson($hoverJob, $measurement, array $headers=[])
	{
		$query = [
			'version' => 'full_json'
		];
 		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJob->hover_job_id.'/measurements.json'.'?'.http_build_query($query), $headers);
		if(!$response) return false;
 		$content = $response->getBody()->getContents();
		$report  =  $hoverJob->hoverReport()->jsonReport()->first();
		$mimeType = 'application/json';
		$fileName = $hoverJob->id.'_json_measurement_reports.json';
		$basePath = 'hover/hover_reports/'. $fileName;
 		$filePath = config('jp.BASE_PATH'). $basePath;
		FlySystem::put($filePath, $content);
		$fileSize = FlySystem::getSize($filePath);
 		if(!$report) {
		 	$report = $this->saveReport($hoverJob, $basePath, $fileName, $mimeType, $fileSize);
		} else {
			$report->file_size = $fileSize;
			$report->save();
		}
 		$this->measurementService->updateHoverMeasurement($measurement, $filePath);
 		return true;
	}
 	/**
	* download xml report from hover
	*
	* @param $jobId
	* @return json file in response
	*/
	private function reportXML($hoverJob, $measurement, array $headers=[])
	{
		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJob->hover_job_id.'/measurements.xml', $headers);
		if(!$response) return false;
 		$content = $response->getBody()->getContents();
		$report  =  $hoverJob->hoverReport()->xmlReport()->first();
		$mimeType = 'application/xml';
		$fileName = $hoverJob->id.'_xml_measurement_reports.xml';
		$basePath = 'hover/hover_reports/'. $fileName;
 		$filePath = config('jp.BASE_PATH'). $basePath;
		FlySystem::put($filePath, $content);
		$fileSize = FlySystem::getSize($filePath);
 		if(!$report) {
		 	$report = $this->saveReport($hoverJob, $basePath, $fileName, $mimeType, $fileSize);
		} else {
			$report->file_size = $fileSize;
			$report->save();
		}
 		return $report;
	}
 	/**
	* download pdf file from hover
	*
	* @param $jobId
	* @return pdf file in response
	*/
	private function reportPdf($hoverJob, $measurement, array $headers=[])
	{
		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJob->hover_job_id.'/measurements.pdf', $headers);
		if(!$response) return null;
 		$content = $response->getBody()->getContents();
		$pdfReport =  $hoverJob->hoverReport()->pdfReport()->first();
		$mimeType = 'application/pdf';
		$fileName = $hoverJob->id.'_pdf_measurement_reports.pdf';
		$basePath = 'hover/hover_reports/'.$fileName;
		$filePath = config('jp.BASE_PATH').$basePath;
 		FlySystem::put($filePath, $content, ['ContentType' => $mimeType]);
		$fileSize = FlySystem::getSize($filePath);
 		if(!$pdfReport) {
			$pdfReport = $this->saveReport($hoverJob, $basePath, $fileName, $mimeType, $fileSize);
			$measurement->is_file   = true;
			$measurement->file_name = $fileName;
			$measurement->file_path = $basePath;
			$measurement->file_mime_type = $mimeType;
		} else {
			$pdfReport->file_size = $fileSize;
			$pdfReport->save();
		}
 		$measurement->file_size = $fileSize;
		$measurement->save();
 		return $pdfReport;
	}
 	/**
	* download xlsx file from hover
	*
	* @param $jobId
	* @return pdf file in response
	*/
	private function reportXLSX($hoverJob, $measurement, array $headers=[])
	{
		$response = $this->sandboxRequestV2->request('GET', 'jobs/'.$hoverJob->hover_job_id.'/measurements.xlsx', $headers);
		if(!$response) return null;
 		$content = $response->getBody()->getContents();
		$xlsx =  $hoverJob->hoverReport()->XLSXReport()->first();
		$mimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
		$fileName = $hoverJob->id.'_xlsx_measurement_reports.xlsx';
		$basePath = 'hover/hover_reports/'.$fileName;
		$filePath = config('jp.BASE_PATH').$basePath;
 		FlySystem::put($filePath, $content, ['ContentType' => $mimeType]);
		$fileSize = FlySystem::getSize($filePath);
 		if(!$xlsx) {
			$this->saveReport($hoverJob, $basePath, $fileName, $mimeType, $fileSize);
		} else {
			$xlsx->file_size = $fileSize;
			$xlsx->save();
		}
 		return $xlsx;
	}

	private function getHoverUser($hoverUserId)
	{
		$hoverUser = $this->getUsers(['search' => $hoverUserId]);
		if(empty($hoverUser['results'])) {
			throw new HoverUserNotExistException("Selected user does not exist on hover.");
		}
		$hoverUserInfo = array_shift($hoverUser['results']);
		$hoverUser = HoverUser::firstOrNew([
			'hover_user_id' => $hoverUserInfo['id']
		]);
		$hoverUser->first_name = $hoverUserInfo['first_name'];
		$hoverUser->last_name  = $hoverUserInfo['last_name'];
		$hoverUser->email = $hoverUserInfo['email'];
		$hoverUser->aasm_state = $hoverUserInfo['aasm_state'];
		$hoverUser->acl_template = $hoverUserInfo['acl_template'];
		$hoverUser->save();

		return $hoverUser;
	}

	private function getHoverClient(){
		$this->hoverClient = $this->repo->getHoverClient();

		if(!$this->hoverClient) {
			throw new AccountNotConnectedException("Hover Account has not connected.");
		}
		$now = \Carbon\Carbon::now()->toDateTimeString();

		if(!($now < $this->hoverClient->expiry_date_time)) {
			$this->hoverClient  = $this->renewToken($this->hoverClient);
		}
		return $this->hoverClient;
	}

	/**
	 * Save 3dModel to storage
	 */
	private function save3dModel($hoverJob)
	{
		if($hoverJob->state != 'complete') return [];

		$data = [
			'job_share' => [
				'job_id' => $hoverJob->hover_job_id,
			],
			'current_user_id' => $hoverJob->hover_user_id,
		];
		$this->hoverClient = $this->getHoverClient();
		$response = $this->request->post('job_shares', [
			'json' => $data,
			'headers' => [
				'Authorization' => "Bearer {$this->hoverClient->access_token}",
			]
		]);
		$jsonResponse = $jsonResponse = json_decode($response->getBody(), 1);
		$url = $jsonResponse['job_share'];
		$hoverJobModel = HoverJobModel::firstOrNew([
			'company_id' => getScopeId(),
			'job_id' => $hoverJob->job_id,
			'hover_job_id' => $hoverJob->hover_job_id
		]);
		$hoverJobModel->image_url = $url['image_url'];
		$hoverJobModel->url = $url['url'];
		$hoverJobModel->save();

		return $hoverJobModel;
	}

	private function updateCaptureHoverUser($job)
	{
		$hoverJob = HoverJob::where('job_id', $job->id)->first();
		$hoverUser = $this->getHoverUser($job->hover_user_id);

		$data = [
			'user_email'    => $hoverUser->email,
			'hover_user_id' => $hoverUser->hover_user_id,
			'owner_id'      => $this->hoverClient->owner_id,
		];
		$oldHoverUserId = $hoverJob->hover_user_id;
		$hoverJob->update($data);
		if($oldHoverUserId != $hoverJob->hover_user_id) {
			$this->reAssingUser($hoverJob);
		}
		return $hoverJob;
	}
}