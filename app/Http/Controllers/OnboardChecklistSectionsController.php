<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\OnboardChecklistSection;
use App\Repositories\OnboardChecklistSectionRepository;
use App\Transformers\OnboardChecklistSectionTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class OnboardChecklistSectionsController extends ApiController
{

    public function __construct(Larasponse $response, OnboardChecklistSectionRepository $repo)
    {
        $this->response = $response;
        $this->repo = $repo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Display a listing of the onboard checklist section.
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();
        $sections = $this->repo->getFilteredOnBoardSection($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $sections = $sections->get();

            return ApiResponse::success($this->response->collection($sections, new OnboardChecklistSectionTransformer));
        }
        $sections = $sections->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($sections, new OnboardChecklistSectionTransformer));
    }


    /**
     * Store a newly created onboard checklist section.
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('title');

        $validator = Validator::make($input, OnboardChecklistSection::getRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $section = $this->repo->save($input);

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Onboard Checklist Section']),
                'section' => $this->response->item($section, new OnboardChecklistSectionTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Display the specified checklist section.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $section = $this->repo->getById($id);

        return ApiResponse::success([
            'section' => $this->response->item($section, new OnboardChecklistSectionTransformer)
        ]);
    }


    /**
     * Update the specified checklist section.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('title');

        $validator = Validator::make($input, OnboardChecklistSection::getUpdateRule($id));
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $section = $this->repo->getById($id);

        try {
            $section->update($input);

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Onboard Checklist Section']),
                'section' => $this->response->item($section, new OnboardChecklistSectionTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Remove the specified checklist section.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $section = $this->repo->getById($id);

        try {
            $section->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Onboard Checklist Section']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Change Section Position
     * @return Response
     */
    public function changePosition()
    {
        $input = Request::onlyLegacy('sections');

        $validator = Validator::make($input, ['sections' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            foreach ($input['sections'] as $section) {
                if (!ine($section, 'id')) {
                    continue;
                }
                $position = ine($section, 'position') ? $section['position'] : 0;

                $this->repo->updatePosition($section['id'], $position);
            }

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Onboard Checklist Sections order'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
