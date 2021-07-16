<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Transformers\QuickbooksEntityErrorTransformer;
use Request;
use App\Models\ApiResponse;
use App\Models\QBEntityError;
use Exception;
use Illuminate\Support\Facades\Validator;

class QuickbooksEntityErrorController extends ApiController{

	protected $response;

	public function __construct(Larasponse $response)
	{
		$this->response = $response;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	public function getError()
	{
		$input = Request::all();
		$validator = Validator::make($input, ['entity'=> 'required', 'entity_id'=> 'required']);

		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}
		try {
			$entityError = QBEntityError::where('entity', $input['entity'])
				->where('entity_id', $input['entity_id'])
				->orderBy('created_at', 'desc')
				->first();

				if($entityError){
					return ApiResponse::success($this->response->item($entityError, new QuickbooksEntityErrorTransformer));
				}

				return $this->getDefaultError($input);

		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	private function getDefaultError($input)
	{
		$defaultError = config('qb.default_error');
		return ApiResponse::success($this->response->item($defaultError, function($defaultError) use($input){
			return [
			    'entity'=> $input['entity'],
			    'entity_id'=> $input['entity_id'],
			    'message' => $defaultError['message'],
			    'error_type' => $defaultError['error_type'],
			    'error_code' => $defaultError['error_code'],
			    'details' => $defaultError['details'],
			    'remedy' => $defaultError['remedy'],
			    'explanation' => $defaultError['explanation'],
			];
		}));
	}

}