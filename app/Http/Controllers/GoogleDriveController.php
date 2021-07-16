<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleAccountNotConnectedException;
use App\Exceptions\GoogleDriveFileNoteFound;
use App\Exceptions\UnableToDownLoadException;
use App\Exceptions\UnsupportedGoogleDriveFile;
use App\Models\ApiResponse;
use App\Services\EstimationService;
use App\Services\Google\GoogleDriveService;
use App\Services\ProposalService;
use App\Services\Resources\ResourceServices;
use App\Transformers\EstimationsTransformer;
use App\Transformers\GoogleDriveFilesTransformer;
use App\Transformers\ProposalsTransformer;
use App\Transformers\ResourcesTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class GoogleDriveController extends ApiController
{

    protected $response;
    protected $googleService;
    protected $resourcesService;
    protected $proposalService;
    protected $estimateService;

    public function __construct(
        Larasponse $response,
        GoogleDriveService $googleService,
        ResourceServices $resourcesService,
        ProposalService $proposalService,
        EstimationService $estimateService
    ) {

        $this->response = $response;
        $this->googleService = $googleService;

        $this->resourcesService = $resourcesService;
        $this->proposalService = $proposalService;
        $this->estimateService = $estimateService;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Get file list
     * GET /google/drive/list
     *
     * @return Response
     */
    public function getList()
    {
        try {
            $input = Request::onlyLegacy('limit', 'page_token', 'name', 'parent', 'type', 'go_nested');

            $results = $this->googleService->getList($input);

            $data = $this->response->collection($results['files'], new GoogleDriveFilesTransformer);
            $data['meta']['next_page_token'] = $results['next_page_token'];

            return ApiResponse::success($data);
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get file data
     * GET /google/drive/{fileId}
     *
     * @return Response
     */
    public function getById($fileId)
    {
        try {
            $file = $this->googleService->getById($fileId);

            return ApiResponse::success([
                'data' => $this->response->item($file, new GoogleDriveFilesTransformer)
            ]);
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get / Download file
     * GET /google/drive/{fileId}/download
     *
     * @return Response
     */
    public function getFile($fileId)
    {
        $input = Request::onlyLegacy('download');

        try {
            $file = $this->googleService->getById($fileId);

            $mimeType = $file->mimeType;

            $size = $file->size;

            if (!is_null($size) && ($size > config('google.max_size_download'))) {
                return ApiResponse::errorGeneral("Unable to download file more than 10MB.");
            }

            if ($this->googleService->isGoogleDoc($mimeType)) {
                $mimeType = $this->googleService->googleMimeConversion($mimeType);

                if (!$mimeType) {
                    throw new UnsupportedGoogleDriveFile(trans('response.error.google_drive_unsupported_file'));
                }
            }

            $content = $this->googleService->getContent($file);

            $response = \response($content, 200);
            $response->header('Content-Type', $mimeType);

            if ($input['download']) {
                $fileName = addExtIfMissing($file->name, $mimeType);
                $response->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
            }

            return $response;
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (UnsupportedGoogleDriveFile $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (UnableToDownLoadException $e) {
            return ApiResponse::errorInternal('Unable to download file.', $e);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Save/Copy Google Drive file
     * POST /google/drive/save_file
     *
     * @return Response
     */
    public function saveFile()
    {
        try {
            $input = Request::onlyLegacy('file_id', 'save_as', 'parent_id', 'job_id');

            $validator = Validator::make($input, [
                'file_id' => 'required',
                'save_as' => 'required|in:resource,proposal,estimate',
                'job_id' => 'required_if:save_as,proposal,estimate',
                'parent_id' => 'required_if:save_as,resource',
            ]);

            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }

            $file = $this->googleService->getById($input['file_id']);

            $size = $file->size;

            if (!is_null($size) && ($size > config('google.max_size_download'))) {
                return ApiResponse::errorGeneral("Unable to download file more than 10MB.");
            }

            $mimeType = $file->mimeType;

            if ($this->googleService->isGoogleDoc($mimeType)) {
                $mimeType = $this->googleService->googleMimeConversion($mimeType);

                if (!$mimeType) {
                    throw new UnsupportedGoogleDriveFile(trans('response.error.google_drive_unsupported_file'));
                }
            }

            $content = $this->googleService->getContent($file);

            $fileName = 'GD_' . addExtIfMissing($file->name, $mimeType);

            // save to resources..
            switch ($input['save_as']) {
                case 'resource':
                    $dataObject = $this->resourcesService->createFileFromContents($input['parent_id'], $content, $fileName, $mimeType);
                    $transformer = new ResourcesTransformer;
                    break;

                case 'proposal':
                    $dataObject = $this->proposalService->createFileFromContents($input['job_id'], $content, $fileName, $mimeType);
                    $transformer = new ProposalsTransformer;
                    break;

                case 'estimate':
                    $dataObject = $this->estimateService->createFileFromContents($input['job_id'], $content, $fileName, $mimeType);
                    $transformer = new EstimationsTransformer;
                    break;

                default:
                    throw new \Exception("Invalid Save type");
                    break;
            }


            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'File']),
                'data' => $this->response->item($dataObject, $transformer)
            ]);
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (UnsupportedGoogleDriveFile $e) {
            return ApiResponse::errorGeneral($this->getMessage());
        } catch (UnableToDownLoadException $e) {
            return ApiResponse::errorInternal('Unable to download file.', $e);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
