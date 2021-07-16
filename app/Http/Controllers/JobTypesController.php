<?php

namespace App\Http\Controllers;

use App\Exceptions\QuickBookException;
use App\Models\ApiResponse;
use App\Models\JobType;
use App\Repositories\JobTypesRepository;
use App\Services\QuickBooks\QuickBookProducts;
use App\Services\QuickBooks\QuickBookService;
use App\Transformers\JobTypesTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\UnauthorizedException;
use DB;
use App\Services\QuickBooks\Facades\Item as QBItem;
use App\Services\QuickBooks\Facades\QuickBooks;

class JobTypesController extends ApiController
{
    /**
     * JobType Repo
     * @var App\Repositories\JobTypesRepository
     */
    protected $repo;

    public function __construct(JobTypesRepository $repo, Larasponse $response, QuickBookProducts $qbProduct, QuickBookService $quickService)
    {
        $this->repo = $repo;
        $this->response = $response;
        $this->qbProduct = $qbProduct;
        $this->quickService = $quickService;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /jobtypes
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $jobTypes = $this->repo->getJobTypes($input);
        $jobTypes = $jobTypes->select( 'id', 'name', 'type', 'trade_id', 'color', 'qb_id', 'qb_account_id')->get();

        return ApiResponse::success($this->response->collection($jobTypes, new JobTypesTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /jobtypes
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('name', 'type', 'trade_id', 'qb_id', 'sync_on_qb', 'qb_account_id');
        $validator = Validator::make($input, JobType::getRules($input));
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ((int)$input['type'] === 2) {
            $type = 'Work Type';
            $input['trade_id'] = $input['trade_id'] ? $input['trade_id'] : 0;
        } else {
            $type = 'Job Type';
            $input['trade_id'] = 0;
        }

        DB::beginTransaction();

        try {
            $jobType = $this->repo->addJobType($input);
            DB::commit();
            return ApiResponse::success([
                'message' => trans('response.success.saved',['attribute' => $type]),
                'job_type' => $jobType
            ]);

        } catch (UnauthorizedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update resource in storage.
     * PUT /jobtypes
     *
     * @return Response
     */
    public function update($id)
    {
        $jobType = $this->repo->getById($id);

        DB::beginTransaction();
        try{

            $input = Request::all();
            $validator = Validator::make($input, JobType::getUpdateRules($input, $id));
            if( $validator->fails() ){
                return ApiResponse::validation($validator);
            }
            $jobType = $this->repo->updateJobType($jobType, $input['name'], $input);
            if((int)$jobType->type === 2){
                $type = 'Work Type';
            }else{
                $type = 'Job Type';
            }
            DB::commit();
            return ApiResponse::success([
                'message' => trans('response.success.updated',['attribute' => $type]),
                'job_type' => $jobType
            ]);
        } catch(UnauthorizedException $e) {
            DB::rollback();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(QuickBookException $e) {
            DB::rollback();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(\Exception $e){
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Delete resource.
     * Delete /jobtypes/{id}
     *
     * @return Response
     */
    public function destroy($id)
    {
        $jobType = $this->repo->deleteJobType($id);
        if ((int)$jobType->type === 2) {
            $type = 'Work Type';
        } else {
            $type = 'Job Type';
        }
        return ApiResponse::success([
            'message' =>trans('response.success.deleted', ['attribute' => $type])
        ]);
    }

    /**
     * get work types with job count
     * GET /jobtypes/with_count
     *
     * @return Response
     */
    public function withJobCount()
    {
        $input = Request::all();
        $jobTypes = $this->repo->getJobTypesWithJobCount($input);

        return ApiResponse::success(['data' => $jobTypes]);
    }


    /**
     * assign work type color
     * PUT - job_types/assign_color/{workTypeId}
     *
     * @param  $workTypeId
     * @return response
     */
    public function assignWorkTypeColor($workTypeId)
    {
        $input = Request::onlyLegacy('color');

        $workType = $this->repo->getWorkTypeById($workTypeId);

        $workType->update(['color' => $input['color']]);

        return ApiResponse::success(['message' => trans('response.success.updated', ['attribute' => 'Color'])]);
    }

    /**
     * Save Product On QuickBook
     *
     * @return Response
     */
    public function saveOnQuickBook()
    {
        $input = Request::onlyLegacy('work_type_id', 'account_id');
        $validator = Validator::make($input, JobType::getSaveOnQuickBookRules($input));
        if( $validator->fails() ){
            return ApiResponse::validation($validator);
        }
        $workType = $this->repo->getWorkTypeById($input['work_type_id']);
        $token = Quickbooks::getToken();
        if(!$token) {
            return ApiResponse::errorGeneral(
                trans('response.error.not_connected', ['attribute' => 'QuickBook Account'])
            );
        }
        try {
            QBItem::createOrUpdateProduct($token, $workType, $input);
            return ApiResponse::success([
                'message' => 'Work Type Synced.',
                'data'  =>  $product
            ]);
        } catch(UnauthorizedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(QuickBookException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
