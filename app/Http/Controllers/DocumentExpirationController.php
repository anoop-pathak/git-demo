<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\DocumentExpirationDate;
use App\Services\Contexts\Context;
use Request;
use Illuminate\Support\Facades\Validator;

class DocumentExpirationController extends ApiController
{

    public function __construct(Context $scope)
    {
        $this->scope = $scope;
        parent::__construct();
    }

    /**
     *
     * POST /document_expire
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('expire_date', 'document_id', 'document_type', 'description');


        $validator = Validator::make($input, DocumentExpirationDate::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $document = DocumentExpirationDate::firstOrNew([
                'object_id' => $input['document_id'],
                'object_type' => $input['document_type'],
                'company_id' => $this->scope->id()
            ]);
            $document->expire_date = $input['expire_date'];
            $document->description = $input['description'];
            $document->save();

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Document Expiry Date']),
                'document_expiration' => $document
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * GET /document_expire/{id} show single documment
     * @param  [int] $id [description]
     * @return [type]     [description]
     */
    public function show($id)
    {
        $input = Request::onlyLegacy('type');

        $document = DocumentExpirationDate::whereCompanyId($this->scope->id())
            ->whereObjectType($input['type'])
            ->whereId($id)
            ->firstOrFail();

        return ApiResponse::success(['document_expiration' => $document]);
    }

    /**
     * Delete document_expire/{id}
     * @param  [int] $id [documentation id]
     * @return [string]     [message]
     */
    public function destroy($id)
    {
        $document = DocumentExpirationDate::whereId($id)
            ->whereCompanyId($this->scope->id())
            ->firstOrFail();

        try {
            $document->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Document Expiry Date'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
