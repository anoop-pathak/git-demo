<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\WarrantyType;
use Illuminate\Support\Facades\Validator;
use App\Repositories\WarrantyTypesRepository;
use App\Transformers\WarrantyTypesTransformer;
use Illuminate\Support\Facades\DB;

class WarrantyTypesController extends ApiController
{
	public function __construct(Larasponse $response, WarrantyTypesRepository $repo)
	{
		parent::__construct();
		$this->response = $response;
        $this->repo = $repo;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();
        $validator = Validator::make($input, ['manufacturer_id'=>'required|integer']);

		if($validator->fails()){
			return ApiResponse::validation($validator);
		}

        try{
			$warrantyTypes = $this->repo->getWarrantyTypes($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$warrantyTypes = $warrantyTypes->get();
				$response = $this->response->collection($warrantyTypes, new WarrantyTypesTransformer);
			} else {
				$warrantyTypes = $warrantyTypes->paginate($limit);
				$response =  $this->response->paginatedCollection($warrantyTypes, new WarrantyTypesTransformer);
			}

            return ApiResponse::success($response);
		} catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
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
		$rules = WarrantyType::getCreateRules();

        if(ine($input, 'manufacturer_id')){
			$rules['name'] = 'required|unique:warranty_types,name,NULL,id,manufacturer_id,'.$input['manufacturer_id'].',company_id,'.getScopeId().',deleted_at,NULL';
		}
		$validator = Validator::make($input, $rules);

        if($validator->fails()){
			return ApiResponse::validation($validator);
		}
		DB::beginTransaction();
		try {
			$warrantyType = $this->repo->save($input['manufacturer_id'], $input['name'], $input);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Warranty']),
				'data' => $this->response->item($warrantyType, new WarrantyTypesTransformer)
			]);
		}catch(\Exception $e) {
            DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

    /**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$warrantyType = $this->repo->getById($id);

        return ApiResponse::success([
			'data'	  => $this->response->item($warrantyType, new WarrantyTypesTransformer)
		]);
	}

    /**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$warrantyType = $this->repo->getById($id);
		$input = Request::all();
		$rules = WarrantyType::getUpdateRules();
		$rules['name'] = 'required|unique:warranty_types,name,'.$warrantyType->id.',id,manufacturer_id,'.$warrantyType->manufacturer_id.',company_id,'.getScopeId().',deleted_at,NULL';
		$validator = Validator::make($input, $rules);

        if($validator->fails()){
			return ApiResponse::validation($validator);
		}
        DB::beginTransaction();
		try {
			$warrantyType = $this->repo->update($warrantyType, $input['name'], $input);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Warranty']),
				'data' => $this->response->item($warrantyType, new WarrantyTypesTransformer)
			]);

		}catch(\Exception $e) {
			DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

    /**
     * @ Assign/UnAssign Estimate Level
     */
    public function assignLevels($id)
    {
        $input = Request::all();
        $validator = Validator::make($input, []);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $levelIds = ine($input, 'level_ids') ? $input['level_ids']: [];
        $warrantyType = $this->repo->getById($id);
        try {
            $this->repo->assignLevels($warrantyType, $levelIds);

            return ApiResponse::success([
                'message' => trans('response.success.assign', ['attribute' => 'Levels']),
            ]);
        } catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function destroy($id)
	{
		$warrantyType = $this->repo->getById($id);
		$warrantyType->levels()->sync([]);
		$warrantyType->delete();

        return ApiResponse::success([
           'message' => trans('response.success.deleted', ['attribute' => 'Warranty']),
        ]);
	}
}