<?php

namespace App\Http\Controllers;


use App\Models\CompanyLogo as CompanyLogoModel;
use App\Services\CompanyLogos\CompanyLogosService as CompanyLogosService;
use App\Services\Contexts\Context;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\CompanyLogoTransformer;
use App\Models\ApiResponse;
use Validator;
use Request;

class CompanyLogosController extends ApiController {
	
	public function __construct(Larasponse $response, CompanyLogosService $logoService, Context $scope)
	{
		$this->logoService = $logoService;
		$this->scope = $scope;
		$this->response = $response;
		
		parent::__construct();
	}
	
	public function upload()
	{
		$inputs = Request::onlyLegacy(['logo_type', 'logo']);
		$validator = Validator::make($inputs, CompanyLogoModel::getUploadLogoRule());
		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		try {
			$logo = $this->logoService->uploadAndUpdateLogo($inputs['logo'], $inputs['logo_type']);
			return ApiResponse::success([
				'message' => 'Logo Uploaded successfully',
				'data'    => $this->response->item($logo, new CompanyLogoTransformer)
			]);
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
	
	public function logos()
	{
		$logos = $this->logoService->getLogos();
		return ApiResponse::success(['data' => $this->response->item($logos, new CompanyLogoTransformer)]);
	}
	
	public function delete()
	{
		$logos = $this->logoService->getLogos();
		
		try {
			$this->logoService->deleteLogosWithFiles($logos);
			return ApiResponse::success([
				'message' => 'Company Logos removed Successfully'
			]);
		} catch(Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}