<?php

namespace App\Http\Controllers;

use App\Exceptions\QuickBookException;
use App\Exceptions\AuthorizationException;
use App\Models\ApiResponse;
use App\Models\Division;
use App\Repositories\DivisionRepository;
use App\Transformers\DivisionTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;


class DivisionsController extends ApiController
{
    /* Larasponse class Instance */
    protected $response;

    /* division Repository */
    protected $repo;

    public function __construct(DivisionRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
        if(Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /divisions
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $divisions = $this->repo->getDivisions($input);

        $limit = isset($input['limit']) ? $input['limit'] : 0;

        if (!$limit) {
            //without pagination
            $divisions = $divisions->get();

            return ApiResponse::success($this->response->collection($divisions, new DivisionTransformer));
        }
        //with pagination
        $divisions = $divisions->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($divisions, new DivisionTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /divisions
     *
     * @return Response
     */
    public function store()
    {
        DB::beginTransaction();
        try {
            $input = Request::onlyLegacy('name', 'qb_id', 'sync_on_qb', 'address', 'address_line_1', 'city', 'state_id', 'country_id', 'zip', 'lat', 'long', 'phone', 'phone_ext', 'email', 'unlink_qb', 'code');
            $validator = Validator::make($input, Division::getRules());

            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }
            $division = $this->repo->save($input);
        } catch (AuthorizationException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Division']),
            'data' => $this->response->item($division, new DivisionTransformer)
        ]);
    }

    /**
     * Display the specified resource.
     * GET /divisions/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $division = $this->repo->getById($id);

        return ApiResponse::success([
            'data' => $this->response->item($division, new DivisionTransformer)
        ]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /divisions/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $division = $this->repo->getById($id);

        DB::beginTransaction();
        try {
            $input = Request::onlyLegacy('name', 'color', 'qb_id', 'sync_on_qb', 'address', 'address_line_1', 'city', 'state_id', 'country_id', 'zip', 'lat', 'long', 'phone', 'phone_ext', 'email', 'code');
            $validator = Validator::make($input, Division::getUpdateRules($id));

            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }


            $input['id'] = $id;
            $division = $this->repo->save($input);
        } catch (AuthorizationException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (QuickBookException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Division']),
            'data' => $this->response->item($division, new DivisionTransformer)
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /divisions/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $division = $this->repo->getById($id);
        try {
            $jobCount = $division->jobs->count();
			$userCount = $division->users->count();
			$financialMacroCount = $division->macros->count();
			$count = $jobCount + $userCount + $financialMacroCount;

            if($count > 0){
				return ApiResponse::errorGeneral(trans('response.error.division',['users' => $userCount, 'jobs'=>$jobCount, 'macors'=>$financialMacroCount]));
            }

            $division->delete();
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Division']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * get divisions with job count
     * GET /divisions/with_count
     *
     * @return Response [count]
     */
    public function getDivisionsWithJobCount()
    {
        // prepare jobs join query..
        $jobQuery = App::make(\App\Repositories\JobsListingRepository::class)
            ->getJobsQueryBuilder();
        $jobsJoinQuery = generateQueryWithBindings($jobQuery);

        $unassignedDivisionsJobCount = $jobQuery->where('jobs.division_id', '=', 0)->count();

        $divisionsWithJobCount = $this->repo->getDivisions()
            ->leftJoin(DB::raw("($jobsJoinQuery) as jobs"), 'jobs.division_id', '=', 'divisions.id')
            ->groupBy('divisions.id')
            ->selectRaw('divisions.id, divisions.name, COUNT(jobs.id) as job_count')
            ->get();

        $divisionsWithJobCount->push(['id' => 0, 'name' => 'Unassigned', 'job_count' => $unassignedDivisionsJobCount]);

        return ApiResponse::success(['data' => $divisionsWithJobCount]);
    }

    /**
     * assign color
     * PUT - divisions/assign_color/{id}
     *
     * @param  $id
     * @return response
     */
    public function assignColor($id)
    {
        $input = Request::onlyLegacy('color');

        $division = $this->repo->getById($id);

        $division->update(['color' => $input['color']]);

        return ApiResponse::success(['message' => trans('response.success.updated', ['attribute' => 'Color'])]);
    }
}
