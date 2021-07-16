<?php

namespace App\Http\Controllers;

use App\Exceptions\FileNotFoundException;
use App\Exceptions\InvalideAttachment;
use App\Exceptions\InvalidResourcePathException;
use App\Models\ApiResponse;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateAttachment;
use App\Repositories\EmailTemplateRepository;
use App\Services\Contexts\Context;
use App\Services\Emails\EmailServices;
use App\Services\Resources\ResourceServices;
use App\Transformers\EmailTemplatesTransformer;
use App\Transformers\ResourcesTransformer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class EmailTemplatesController extends ApiController
{

    public function __construct(
        Larasponse $response,
        Context $scope,
        EmailTemplateRepository $repo,
        EmailServices $emailService
    ) {
        $this->scope = $scope;
        $this->response = $response;
        $this->repo = $repo;
        $this->emailService = $emailService;
        if (Request::get('includes')) {
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
        $emailTemplate = $this->repo->getFilteredEmailTemplate($input);

        $limit = isset($input['limit']) ? $input['limit'] : \config('jp.pagination_limit');
        if (!$limit) {
            $emailTempates = $emailTemplate->get();
            return ApiResponse::success($this->response->collection($emailTempates, new EmailTemplatesTransformer));
        }
        $emailTemplates = $emailTemplate->paginate($limit);
        return ApiResponse::success($this->response->paginatedCollection($emailTemplates, new EmailTemplatesTransformer));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy(
            'template',
            'title',
            'attachments',
            'subject',
            'stage_code',
            'to',
            'cc',
            'bcc',
            'send_to_customer',
            'recipients_setting'
        );

        $validator = Validator::make($input, EmailTemplate::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $this->emailService->createEmailTemplate(
                $input['title'],
                $input['template'],
                true,
                \Auth::user()->id,
                (array)$input['attachments'],
                $input['subject'],
                $input
            );

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Email Template'])
            ]);
        } catch (FileNotFoundException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (InvalideAttachment $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $emailTemplate = EmailTemplate::whereId($id)
            ->whereCompanyId($this->scope->id())
            ->firstOrFail();
        try {
            return ApiResponse::success([
                'data' => $this->response->item($emailTemplate, new EmailTemplatesTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }


    /**
     * Update the specified resource in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('template', 'title', 'attachments', 'subject', 'stage_code', 'to', 'cc', 'bcc', 'send_to_customer', 'active', 'recipients_setting');
        $validator = Validator::make($input, EmailTemplate::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $emailTemplate = EmailTemplate::whereId($id)
            ->whereCompanyId($this->scope->id())
            ->firstOrFail();
        try {
            $this->emailService->updateEmailTemplate(
                $emailTemplate,
                $input['title'],
                $input['template'],
                $input['active'],
                (array)$input['attachments'],
                $input['subject'],
                $input
            );
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Email Template'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function attach_file()
    {
        $input = Request::onlyLegacy('type', 'value', 'template_id', 'file');
        $validator = Validator::make($input, EmailTemplate::getAttachFileRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $resource = $this->emailService->templateAttachFile(
                $input['type'],
                $input['value'],
                $input['template_id'],
                $input['file']
            );
            return ApiResponse::success([
                'message' => Lang::get('response.success.file_uploaded'),
                'data' => $this->response->item($resource, new ResourcesTransformer)
            ]);
        } catch (FileNotFoundException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (InvalideAttachment $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidResourcePathException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function delete_file()
    {
        $input = Request::onlyLegacy('resource_id', 'template_id');
        $validator = Validator::make($input, EmailTemplate::getDeleteFileRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            \App::make(ResourceServices::class)->removeFile($input['resource_id']);
            EmailTemplateAttachment::attachment($input['resource_id'], $input['template_id'])->delete();
            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'File'])
            ]);
        } catch (FileNotFoundException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $emailTemplate = EmailTemplate::whereId($id)
            ->whereCompanyId($this->scope->id())
            ->firstOrFail();
        try {
            $emailTemplate->recipient()->delete();
            $emailTemplate->delete();

            return ApiResponse::success([
                'message' => Lang::get('response.success.deleted', ['attribute' => 'Email Template'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function activate($id)
    {
        $status = 'acitvate';
        $input = Request::onlyLegacy('active');
        $validator = Validator::make($input, ['active' => 'required|boolean']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $emailTemplate = EmailTemplate::whereId($id)
            ->whereCompanyId($this->scope->id())
            ->firstOrFail();
        try {
            $emailTemplate->update(['active' => $input['active']]);
            if (!empty($emailTemplate->active)) {
                return ApiResponse::success([
                    'message' => Lang::get('response.success.activate', ['attribute' => 'Email Template'])
                ]);
            }
            return ApiResponse::success([
                'message' => Lang::get('response.success.deactivate', ['attribute' => 'Email Template'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Email template count workflow stage wise.
     * Get /emails/template/stage_wise_count
     *
     * @return Response
     */
    public function stageWiseCount()
    {
        // get active workflow's stage query builder..
        $workflowRepo = App::make(\App\Repositories\WorkflowRepository::class);
        $stages = $workflowRepo->getActiveWorkflowStages();

        // get count data stage wise..
        $data = $stages->leftJoin(
            DB::raw('(select COUNT(id) as count, stage_code from email_templates GROUP BY stage_code) as email_templates'),
            'workflow_stages.code',
            '=',
            'email_templates.stage_code'
        )->select(
            'workflow_stages.code',
            'workflow_stages.name',
            'workflow_stages.color',
            DB::raw('IFNULL(email_templates.count, 0) as count')
        )->get();

        return ApiResponse::success(['data' => $data]);
    }
}
