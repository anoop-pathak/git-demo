<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\OnboardChecklist;
use App\Repositories\OnboardChecklistRepository;
use App\Transformers\OnboardChecklistTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class OnboardChecklistsController extends Controller
{

    public function __construct(Larasponse $response, OnboardChecklistRepository $repo)
    {
        $this->response = $response;
        $this->repo = $repo;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the checklist.
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $onboard = $this->repo->getFilteredCheckList($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $onboard = $onboard->get();

            return ApiResponse::success($this->response->collection($onboard, new OnboardChecklistTransformer));
        }
        $onboard = $onboard->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($onboard, new OnboardChecklistTransformer));
    }


    /**
     * Store a newly created checklist.
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, OnboardChecklist::getRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $onboard = $this->repo->save($input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Onboard Checklist']),
                'checklist' => $this->response->item($onboard, new OnboardChecklistTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Display the specified checklist.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $onboard = $this->repo->getById($id);

        return ApiResponse::success([
            'checklist' => $this->response->item($onboard, new OnboardChecklistTransformer)
        ]);
    }


    /**
     * Update the specified checklist.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('title', 'video_url', 'action', 'is_required');

        $validator = Validator::make($input, ['title' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $onboard = $this->repo->getById($id);

        try {
            $onboard->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Onboard Checklist']),
                'checklist' => $this->response->item($onboard, new OnboardChecklistTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Remove the specified checklist.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $onboard = $this->repo->getById($id);

        try {
            $onboard->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Onboard Checklist']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Save company onboard checklist
     * GEt companies/onboard_checklist
     * @return Response
     */
    public function saveOnboardChecklist()
    {
        $input = Request::onlyLegacy('checklist_id', 'selected');

        $validator = Validator::make($input, ['checklist_id' => 'required|exists:onboard_checklists,id']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $this->repo->saveCompanyChecklist($input['checklist_id'], $input['selected']);

            return ApiResponse::success([]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get company Check list
     * @return Json company checklist
     */
    public function getCompanyChecklist()
    {
        try {
            $data = $this->repo->getCompanyChecklist();

            return ApiResponse::success($data);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
