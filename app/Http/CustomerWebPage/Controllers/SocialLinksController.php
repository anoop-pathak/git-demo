<?php

namespace App\Http\CustomerWebPage\Controllers;

use App\Http\Controllers\ApiController;
use App\Models\ApiResponse;
use App\Http\CustomerWebPage\Transformers\JobsTransformer;
use Sorskod\Larasponse\Larasponse;
use Request;
use App\Repositories\JobRepository;
use App\Models\Setting;
use Exception;
use Settings;
use Illuminate\Http\Request as RequestClass;

class SocialLinksController extends ApiController
{
	protected $response;
	protected $repo;

	public function __construct(Larasponse $response, JobRepository $repo)
	{
		parent::__construct();

		$this->response = $response;
		$this->repo = $repo;

		if (Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	public function getSocialLinks(RequestClass $request)
	{
		$jobToken = getJobToken($request);

		try{
			$job = $this->repo->getByShareToken($jobToken);
			$companyId = $job->company_id;
			$socialLinks = $this->getLinks($companyId);

			return ApiResponse::success(['data' => $socialLinks]);
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	private function getLinks($compnayId)
	{
		return  Setting::whereCompanyId($compnayId)  
						->where('key','SOCIAL_LINKS')
						->whereNotNull('value')
						->select('value')
						->first();
	}

	public function getReviewLink(RequestClass $request)
	{
		$jobToken = getJobToken($request);
		try{
			$job = $this->repo->getByShareToken($jobToken);
			$companyId = $job->company_id;

			$placeId = Setting::whereCompanyId($companyId)
								->where('key','GOOGLE_CUSTOMER_REVIEW_PLACE_ID')
								->whereNotNull('value')
								->select('value')
								->first();
			$link = null;
			if($placeId) {
				$link = config('jp.google_customer_review_link') . $placeId->value;
			}
			$googleCustomerReviewLink['link'] = $link;
			
			return ApiResponse::success(['data' => $googleCustomerReviewLink]);

		} catch(\Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}
