<?php

namespace App\Http\Controllers;

use App\Exceptions\GoogleAccountNotConnectedException;
use App\Exceptions\GoogleDriveFileNoteFound;
use App\Exceptions\InvalideAttachment;
use App\Exceptions\InvalidFileException;
use App\Models\ApiResponse;
use App\Models\Estimation;
use App\Models\EstimationPage;
use App\Models\Job;
use App\Models\JobInsuranceDetails;
use App\Repositories\EstimationsRepository;
use App\Services\EstimationService;
use App\Services\Google\GoogleSheetService;
use FlySystem;
use App\Services\Xactimate\Xactimate;
use App\Transformers\EstimationsTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Services\SecurityCheck;
use App\Services\Folders\FolderService;
use App\Services\Folders\MoveFileService;
use App\Repositories\JobRepository;
use App\Events\Folders\JobEstimationRestoreFile;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;
use Illuminate\Support\Facades\Event;
use App\Models\Folder;

class EstimationsController extends ApiController
{

    /**
     * Representatives Repo
     * @var \App\Repositories\EstimationsRepository
     */
    protected $repo;
    protected $xactimateService;
    protected $folderService;
    protected $moveFolderFileService;

	public function __construct(
        EstimationsRepository $repo,
        Larasponse $response,
        EstimationService $service,
        FolderService $folderService,
        Xactimate $xactimateService,
        MoveFileService $moveFolderFileService,
        JobRepository $jobRepo
    ){
        $this->repo = $repo;
        $this->response = $response;
        $this->service = $service;
        $this->xactimateService = $xactimateService;
        $this->folderService = $folderService;
		$this->moveFolderFileService = $moveFolderFileService;
        $this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    public function index()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['job_id' => 'required_without:deleted_estimations']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $estimations = $this->repo->get($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($estimations, new EstimationsTransformer));
        }

        return ApiResponse::success($this->response->paginatedCollection($estimations, new EstimationsTransformer));
    }

    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, Estimation::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::find($input['job_id']);
        if (!$job) {
            return ApiResponse::errorNotFound(trans('response.error.invalid', ['attribute' => 'job id']));
        }

        if (ine($input, 'serial_number')
            && $this->repo->isExistSerialNumber($input['serial_number'])
            && !ine($input, 'save_as')) {
            $data['serial_number'] = $this->repo->getSerialNumber();

            return ApiResponse::errorGeneral(trans('response.error.serial_number_already_exist', ['attribute' => 'Estimate']), [], $data);
        }

        // handle single page
        if (ine($input, 'template')) {
            $input['pages'][0]['template'] = $input['template'];
            $input['pages'][0]['template_cover'] = ine($input, 'template_cover') ? $input['template_cover'] : "";
        }

        try {
            $createdBy = \Auth::id();
            $estimation = $this->service->create($input['job_id'], $input['pages'], $createdBy, $input);
            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Estimate']),
                'data' => $this->response->item($estimation, new EstimationsTransformer)
            ]);
        } catch(FolderNotExistException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function show($id)
    {
        $estimation = $this->repo->getById($id);

        return ApiResponse::success([
            'data' => $this->response->item($estimation, new EstimationsTransformer)
        ]);
    }

    public function update($id)
    {
        $estimation = $this->repo->getById($id);

        $input = Request::all();

        $validator = Validator::make($input, Estimation::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $input['is_file'] = 0;

        // handle single page
        if (ine($input, 'template')) {
            $input['pages'][0]['template'] = $input['template'];
            $input['pages'][0]['template_cover'] = ine($input, 'template_cover') ? $input['template_cover'] : "";
        }
        $this->service->update($estimation, $input);
        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Estimate']),
            'data' => $this->response->item($estimation, new EstimationsTransformer)
        ]);
        return ApiResponse::errorInternal();
    }

    public function delete_page($pageId)
    {
        $page = EstimationPage::findOrFail($pageId);
        try {
            $this->service->deletePage($page);
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Page'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal();
        }
    }

    public function restore($id)
	{
		if(!Auth::user()->isAuthority()) {
			return ApiResponse::errorForbidden();
		}

		if(!SecurityCheck::verifyPassword()) {
			return SecurityCheck::$error;
		}

		$estimation = $this->repo->getDeletedById($id);

		try {
            $estimation->restore();
            Event::fire('JobProgress.Templates.Events.Folder.restoreFile', new JobEstimationRestoreFile($id));
		} catch (\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

		return ApiResponse::success([
			'message' => trans('response.success.restored', ['attribute' => 'Estimate'])
		]);
	}

    public function destroy($id)
    {
        $estimation = $this->repo->getById($id);
        if ($estimation->delete()) {
            Event::fire('JobProgress.Templates.Events.Folder.deleteFile', new JobEstimationDeleteFile($id));
            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Estimate'])
            ]);
        }
        return ApiResponse::errorInternal();
    }

    public function file_upload()
    {
        $input = Request::onlyLegacy('job_id','file', 'make_pdf', 'image_base_64', 'rotation_angle', 'title', 'parent_id');
        $validator = Validator::make($input, Estimation::getFileUploadRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::find($input['job_id']);

        if (!$job) {
            return ApiResponse::errorNotFound(trans('response.error.invalid', ['attribute' => 'job id']));
        }

        try {

            $estimation = $this->service->uploadFile(
                $input['job_id'],
                $input['file'],
                $input['image_base_64'],
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'data' => $this->response->item($estimation, new EstimationsTransformer)
            ]);
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function upload_multiple_file()
    {

        $input = Request::onlyLegacy('job_id', 'files', 'make_pdf');
        $validator = Validator::make($input, Estimation::getUploadMultipleFilesRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = Job::find($input['job_id']);
        if (!$job) {
            return ApiResponse::errorNotFound(trans('response.success.invalid', ['attribute' => 'job id']));
        }

        try {
            foreach ($input['files'] as $file) {
                $this->service->uploadFile($input['job_id'], $file, $input);
            }

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function get_file($id)
    {
        $input = Request::onlyLegacy('base64_encoded', 'download');
        $estimation = $this->repo->getById($id);

        if (empty($estimation->file_path)) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'File']));
        }

        $fullPath = config('jp.BASE_PATH') . $estimation->file_path;

        // if base64 image required..
        if ((bool)$input['base64_encoded']) {
            return $this->getBase64EncodedImage($fullPath);
        }

        if (!$input['download']) {
            $fileResource = FlySystem::read($fullPath);
            $response = \response($fileResource, 200);
            $response->header('Content-Type', $estimation->file_mime_type);
            $response->header('Content-Disposition', 'filename="' . $estimation->file_name . '"');
            return $response;
        } else {
            return FlySystem::download($fullPath, $estimation->file_name);

            // $response->header('Content-Disposition' ,'attachment; filename="'.$estimation->file_name.'"');
        }
    }

    public function rename($id)
    {
        $estimation = $this->repo->getById($id);
        $input = Request::onlyLegacy('title');
        $validator = Validator::make($input, ['title' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        DB::beginTransaction();
        try {
            $this->service->rename($estimation, $input['title']);
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleAccountNotConnectedException $e) {
            DB::rollback();

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.rename', ['attribute' => 'Estimate'])
        ]);
    }

    public function download($id)
    {
        try {
            $estimation = $this->repo->getById($id);

            if ($estimation->type == Estimation::GOOGLE_SHEET) {
                $googleSheetService = App::make(GoogleSheetService::class);

                return $googleSheetService->downloadFile($estimation->google_sheet_id);
            }

            $fullPath = config('jp.BASE_PATH') . $estimation->file_path;

            return FlySystem::download($fullPath, $estimation->file_name);

            // $fileResource = FlySystem::read($fullPath);
            // $response = \response($fileResource, 200);
            // $response->header('Content-Type', $estimation->file_mime_type);
            // $response->header('Content-Disposition' ,'filename="'.$estimation->file_name.'"');

            // return $response;
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function edit_image_file($id)
    {
        $estimation = $this->repo->getById($id);
        $input = Request::onlyLegacy('base64_string', 'rotation_angle');
        $validator = Validator::make($input, ['base64_string' => 'required|string']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $estimation = $this->editImageFile($estimation, $input['base64_string'], $input);
            return ApiResponse::success($this->response->item($estimation, new EstimationsTransformer));
        } catch (InvalidFileException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function send_mail($id)
    {
        $estimation = $this->repo->getById($id);
        $input = Request::onlyLegacy('subject', 'content', 'email');
        $validator = Validator::make($input, Estimation::getSendMailRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        if (empty($estimation->file_path)) {
            return ApiResponse::errorInternal(trans('response.error.not_sent'));
        }
        $attachment[0] = [
            'type' => 'estimate',
            'value' => $id
        ];
        try {
            $job = $estimation->job;

            // set meta
            $meta['job_id'] = (array)$job->id;
            $meta['customer_id'] = $job->customer_id;

            App::make(\App\Services\Emails\EmailServices::class)->sendEmail(
                $input['subject'],
                $input['content'],
                (array)$input['email'],
                [],
                [],
                $attachment,
                \Auth::id(),
                $meta
            );
            return ApiResponse::success(['message' => trans('response.success.email_sent')]);
        } catch (InvalideAttachment $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Rotate Image File
     * Post /estimations/{id}/rotate_image
     * @param  Int $id Estimate Id
     * @return Response
     */
    public function rotate_image_file($id)
    {
        $input = Request::onlyLegacy('rotation_angle');
        $validator = Validator::make($input, ['rotation_angle' => 'numeric']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($input['rotation_angle'] == '') {
            return ApiResponse::errorGeneral(trans('response.error.roation_angle_required'));
        }

        $estimate = $this->repo->getById($id);
        if (!in_array($estimate->file_mime_type, config('resources.image_types'))) {
            return ApiResponse::errorGeneral(trans('response.error.only_image_rotate'));
        }

        try {
            $estimation = $this->service->rotateImage($estimate, $input['rotation_angle']);

            return ApiResponse::success([
                'message' => trans('response.success.rotated', ['attribute' => 'Image']),
                'data' => $this->response->item($estimation, new EstimationsTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Create google sheet
     * Post estimations/create_google_sheet
     * @return Estimate
     */
    public function createGoogleSheet()
    {
        $input = Request::all();

        $validator = Validator::make($input, Estimation::getCreateGoogleSheetRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $estimations = $this->service->createGoogleSheet(
                $input['job_id'],
                $input
            );
        } catch(FolderNotExistException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleDriveFileNoteFound $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (GoogleAccountNotConnectedException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Estimate']),
            'data' => $this->response->item($estimations, new EstimationsTransformer)
        ]);
    }

    /**
     * Estimate Share on home owner page
     * @param  int $id Estimate Id
     * @return Response
     */
    public function shareOnHomeOwnerPage($id)
    {
        $input = Request::onlyLegacy('share');
        $estimate = $this->repo->getById($id);

        if ($estimate->type == Estimation::GOOGLE_SHEET) {
            return ApiResponse::errorGeneral('Invalid operation for Google sheet');
        }

        $estimate->share_on_hop = ($input['share']);
        $estimate->share_on_hop_at = ($input['share']) ? Carbon::now() : null;
        $estimate->update();

        $msg = 'Shared on Customer Web Page.';
        if (!$estimate->share_on_hop) {
            $msg = 'Removed from Customer Web Page.';
        }

        return ApiResponse::success([
            'message' => trans($msg)
        ]);
    }

    /**
     * parse xactimate file
     *
     * POST - /estimations/xactimate_pdf_parser
     *
     * @return $response
     */
    public function parseXactimateFile()
    {
        $input = Request::onlyLegacy('xactimate');

        $validator = Validator::make($input, ['xactimate' => 'required|mimes:pdf']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $response = $this->xactimateService->parsePdf($input['xactimate']);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return $response;
    }

    /**
     * update job insurance details
     *
     * PUT - /estimations/update_job_insurance
     *
     * @return response
     */
    public function updateJobInsuranceDetail()
    {
        $input = Request::onlyLegacy('estimation_id');

        $validator = Validator::make($input, ['estimation_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $estimate = $this->repo->getById($input['estimation_id']);

        if ($estimate->type !== Estimation::XACTIMATE) {
            return ApiResponse::errorGeneral('Please select an insurance');
        }

        $insuranceData = $estimate->worksheet->insurance_meta;

        if (!$insuranceData) {
            return ApiResponse::errorGeneral('Insurance details are missing.');
        }

        $data = [
            'insurance_number' => $insuranceData->claim_number,
            'rcv' => $insuranceData->rcv_total,
            'acv' => $insuranceData->acv_total,
            'policy_number' => $insuranceData->policy_number,
            'depreciation'		=> $insuranceData->depreciation_total,
			'deductable_amount'	=> 0,
			'supplement'		=> 0,
			'net_claim'			=> $insuranceData->acv_total,
			'total'				=> $insuranceData->rcv_total,
        ];

        try {
            $job = $estimate->job;

            if ($job->isProject()) {
                return ApiResponse::errorGeneral('Please select insurance with parent job.');
            }

            if ($job->insuranceDetails) {
                $job->insuranceDetails()->update($data);
            } else {
                $JobInsurance = new JobInsuranceDetails($data);
                $JobInsurance->job_id = $job->id;
                $JobInsurance->save();
            }

            DB::table('jobs')->whereCompanyId($job->company_id)
				->whereId($job->id)
  				->update(['insurance' => true]);

            Estimation::whereCompanyId(getScopeId())
                ->whereJobId($estimate->job_id)
                ->where(function($query) {
					$query->whereNotNull('xactimate_file_path')
						->orWhere('estimation_type', Estimation::XACTIMATE);
				})->update(['job_insurance' => false]);

            $estimate->job_insurance = true;
            $estimate->save();
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success(['message' => trans('response.success.updated', ['attribute' => 'Job insurance'])]);
    }

    /**
	 * Create folder in estimations.
	 *
	 * POST - /estimations/folder
	 * @return json response.
	 */
	public function createFolder()
	{
		$inputs = Request::all();
		$validator = Validator::make($inputs, Estimation::getFolderRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$job = $this->jobRepo->getById($inputs['job_id']);

		try {
			$item = $this->folderService->createEstimationFolder($inputs);

			return ApiResponse::success([
				'data' => $this->response->item($item, new EstimationsTransformer)
			]);
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
			return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
		}
	}

	/**
	 * Move files to the directory.
	 *
	 * @return json
	 */
	public function moveFilesToFolder()
	{
		$inputs = Request::all();
		$validator = Validator::make($inputs, Estimation::getDocumentMoveRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		DB::beginTransaction();
		try {
			$ids = (array)$inputs['ids'];
			$parentId = issetRetrun($inputs, 'parent_id');
			$type = Folder::JOB_ESTIMATION;
			$items = $this->moveFolderFileService->moveEstimationFiles($ids, $type, $parentId, $inputs);
			DB::commit();
			return ApiResponse::success($this->response->collection($items, new EstimationsTransformer));
		} catch(FolderNotExistException $e) {
            DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {
            DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
		}
	}


    /************************ Private function **********************/

    /**
     * This is to edit only image files
     */
    private function editImageFile(Estimation $estimation, $base64_string, $meta = [])
    {
        $previousFilePath = null;
        if (!empty($estimation->file_path)) {
            $previousFilePath = config('jp.BASE_PATH') . $estimation->file_path;
        }

        $previousThumbPath = null;
        if (!empty($estimation->thumb)) {
            $previousThumbPath = config('jp.BASE_PATH') . $estimation->thumb;
        }

        $fullPath = config('jp.BASE_PATH') . 'estimations';
        $physicalName = Carbon::now()->timestamp . "_{$estimation->id}_image.jpg";

        //for image rotation
        $rotationAngle = null;
        if (ine($meta, 'rotation_angle')) {
            $rotationAngle = $meta['rotation_angle'];
        }

        $uploadedFile = uploadBase64Image($base64_string, $fullPath, $physicalName, $rotationAngle);

        if (!$uploadedFile) {
            throw new InvalidFileException(trans('response.error.invalid', ['attribute' => 'File Type']));
        }

        // create thumb..
        $imagePath = "{$fullPath}/{$physicalName}";
        $thumbPath = "{$fullPath}/thumb/$physicalName";
        $thumb = \Image::make(\FlySystem::read($imagePath));
        if ($thumb->height() > $thumb->width()) {
            $thumb->heighten(200, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $thumb->widen(200, function ($constraint) {
                $constraint->upsize();
            });
        }
        // save thumb..
        FlySystem::put($thumbPath, $thumb->encode()->getEncoded());

        $estimation->file_size = $uploadedFile['size'];
        $estimation->file_path = 'estimations/' . $uploadedFile['name'];
        $estimation->thumb = 'estimations/thumb/' . $uploadedFile['name'];
        $estimation->file_mime_type = $uploadedFile['mime_type'];
        $estimation->save();

        //delete the file physically
        if ($previousFilePath) {
            FlySystem::delete($previousFilePath);
        }

        if ($previousThumbPath) {
            FlySystem::delete($previousThumbPath);
        }

        return $estimation;
    }

    private function getBase64EncodedImage($filePath)
    {
        try {
            $data = getBase64EncodedData($filePath);
            return ApiResponse::success(['data' => $data]);
        } catch (InvalidFileException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
