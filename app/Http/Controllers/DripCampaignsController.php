<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Transformers\DripCampaignsTransformer;
use App\Repositories\DripCampaignRepository;
use App\Services\DripCampaigns\DripCampaignService;
use App\Services\Grid\CommanderTrait;
use App\Exceptions\InvalideAttachment;
use App\Models\ApiResponse;
use App\Models\DripCampaign;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Queue;
use Exception;
use Illuminate\Support\Facades\Artisan;

class DripCampaignsController extends ApiController {

	use CommanderTrait;

	public function __construct(Larasponse $response, DripCampaignService $service, DripCampaignRepository $repo) {

		$this->response = $response;
		$this->repo = $repo;
		$this->service  = $service;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();
		try{

			$campaigns = $this->repo->getFilteredCampaigns($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

			if(!$limit) {
				$campaigns = $campaigns->get();

				return ApiResponse::success($this->response->collection($campaigns, new DripCampaignsTransformer));
			}
			$campaigns = $campaigns->paginate($limit);

			return ApiResponse::success($this->response->paginatedCollection($campaigns, new DripCampaignsTransformer));

		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * Get single resouce by id.
	 *
	 * @param integer $id
	 * @return Response
	 */
	public function show($id)
	{
		try{

			$campaign = $this->repo->getById($id);
			return ApiResponse::success(['data' => $this->response->item($campaign, new DripCampaignsTransformer)]);
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
        $input = Request::all();

        $scopes = ['emailCampaign', 'campaignRecipent', 'complexValidation'];
        $validate = Validator::make($input, DripCampaign::validationRules($scopes));

        if($validate->fails() ) {

            return ApiResponse::validation($validate);
        }
		try {
			$dripCampaign = $this->execute("\App\Commands\DripCampaignCommand", ['input' => $input]);
            Queue::push('\App\Handlers\Events\DripCampaignQueueHandler', ['id' => $dripCampaign->id]);

			if($dripCampaign) {
				return ApiResponse::success([
					'message' => trans('response.success.saved', ['attribute'=>'Email Recurring']),
					'drip_campaign' => $this->response->item($dripCampaign, new DripCampaignsTransformer)
				]);
			}

		} catch(InvalideAttachment $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

	}

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function cancel($id)
	{
		$input = Request::all();
		$dripCampaign = $this->repo->getCampaignById($id);

		$validator = Validator::make($input, DripCampaign::getCancelRules());
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		try {
			$this->service->cancelDripCampaign($dripCampaign, $input);
		} catch(Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}

		return ApiResponse::success([
			'message'   => trans('response.success.canceled', ['attribute' => 'Email Recurring']),
		]);
	}

	public function sendDripCampaignScheduler()
	{
		$input = Request::all();
		$validator = Validator::make($input, DripCampaign::getSchedulerRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$dripCampaign = $this->repo->getCampaignById($input['campaign_id']);

		try {
			Artisan::call('command:send_drip_campaign_scheduler', ['drip_campaign_id' => $dripCampaign->id, 'date' => $input['date']]);

		} catch(Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}

		return ApiResponse::success([
			'message'   => trans('response.success.send', ['attribute' => 'Email Schedular']),
		]);
	}
}