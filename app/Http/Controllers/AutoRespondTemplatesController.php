<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Repositories\AutoRespondTemplateRepository;
use App\Services\Contexts\Context;
use App\Transformers\AutoRespondTemplateTransformer;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class AutoRespondTemplatesController extends ApiController
{

    /**
     * Compnay Scope
     * @var \App\Services\Contexts\Context
     */
    protected $scope;

    /**
     * Auto Respond Template Repo
     * @var \App\Repositories\AutoRespondTemplateRepository
     */
    protected $repo;

    protected $response;

    public function __construct(Context $scope, AutoRespondTemplateRepository $repo, Larasponse $response)
    {
        $this->scope = $scope;
        $this->repo = $repo;
        $this->response = $response;

        parent::__construct();
    }

    /**
     * Store a newly created resource in storage.
     * POST /auto_respond/template
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('subject', 'content', 'active');

        try {
            $template = $this->repo->createOrUpdateTemplate($input['subject'], $input['content'], (bool)$input['active']);

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Auto Respond Template']),
                'data' => $this->response->item($template, new AutoRespondTemplateTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Update Activation/Deactivation the specified resource
     * PUT /auto_respond/template/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function markAsActive()
    {
        $input = Request::onlyLegacy('active');
        $validator = Validator::make($input, ['active' => 'required|boolean']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $template = $this->repo->getTemplate();

        if (!$template) {
            return ApiResponse::errorGeneral('Template not found. Please create first.');
        }

        try {
            $template->update(['active' => $input['active']]);

            if (!empty($template->active)) {
                return ApiResponse::success([
                    'message' => Lang::get('response.success.activated', ['attribute' => 'Email auto responding'])
                ]);
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.deactivated', ['attribute' => 'Email auto responding'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function getTemplate()
    {
        $input = Request::all();
        $template = $this->repo->getTemplate();

        if (!$template) {
            return ApiResponse::success([]);
        }

        return ApiResponse::success([
            'data' => $this->response->item($template, new AutoRespondTemplateTransformer)
        ]);
    }
}
