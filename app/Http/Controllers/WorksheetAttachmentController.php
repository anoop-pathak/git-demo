<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Message;
use App\Models\WorksheetAttachment;
use App\Services\Contexts\Context;
use FlySystem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;

class WorksheetAttachmentController extends Controller
{
    protected $model;
    protected $scope;

    public function __construct(WorksheetAttachment $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
        parent::__construct();
    }

    /**
     * get attachment by specific id
     * @return attachment details [array]
     */
    public function get()
    {
        $input = Request::onlyLegacy('attachment_ids');

        $validator = Validator::make($input, ['attachment_ids' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $files = $this->model->whereIn('id', (array)$input['attachment_ids'])->get();

        return ApiResponse::success(['data' => $files]);
    }

    /**
     * store attachment
     * @return message and attachment details
     */
    public function store()
    {
        $input = Request::onlyLegacy('file');

        $validator = Validator::make($input, WorksheetAttachment::getFileRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $file = $input['file'];

            $fullPath = config('jp.BASE_PATH');

            $originalName = $file->getClientOriginalName();
            $physicalName = 'worksheet_attachments/' . Carbon::now()->timestamp . '_' . $originalName;
            $size = $file->getSize();
            $mimeType = $file->getMimeType();

            /* save file */
            FlySystem::writeStream($fullPath . '/' . $physicalName, $file, ['ContentType' => $mimeType]);
            /* save attachment */
            $attachment = $this->model->create([
                'company_id' => $this->scope->id(),
                'name' => $originalName,
                'size' => $size,
                'mime_type' => $mimeType,
                'path' => $physicalName,
            ]);
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.added', ['attribute' => 'Attachment']),
                'data' => $attachment,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * delete attachment
     * @return reponse
     */
    public function destroy($id)
    {
        $file = $this->model->findOrFail($id);
        try {
            if (!empty($file->path)) {
                $filePath = config('jp.BASE_PATH') . $file->path;
                FlySystem::delete($filePath);
            }

            $file->delete(); //delete from database

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Attachment']),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * get/download attachment by specific id
     * @return file
     */
    public function getFile()
    {
        $input = Request::onlyLegacy('id', 'download');

        $validator = Validator::make($input, ['id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $file = $this->model->findOrFail($input['id']);

        $fullPath = config('jp.BASE_PATH') . $file->path;

        $fileResource = FlySystem::read($fullPath);

        $response = \response($fileResource, 200);

        $response->header('Content-Type', $file->mime_type);
        if (!ine($input, 'download')) {
            $response->header('Content-Disposition', 'filename="' . $file->name . '"');
        } else {
            $response->header('Content-Disposition', 'attachment; filename="' . $file->name . '"');
        }

        return $response;
    }
}
