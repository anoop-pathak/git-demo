<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Measurement;
use App\Models\MeasurementFormula;
use App\Services\Contexts\Context;
use App\Services\Measurement\MeasurementService;
use App\Transformers\MeasurementFormulaTransformer;
use App\Transformers\MeasurementTransformer;
use App\Transformers\TradesTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Models\MeasurementValue;
use App\Exceptions\InvalidFileException;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use FlySystem;
use App\Events\Folders\JobMeasurementDeleteFile;
use App\Services\Folders\FolderService;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Event;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;
use App\Repositories\JobRepository;

class MeasurementController extends ApiController
{

    public function __construct(MeasurementService $service, Larasponse $response, Context $scope, JobRepository $jobRepo, FolderService $folderService)
    {
        $this->service = $service;
        $this->response = $response;
        $this->scope = $scope;
        $this->folderService = $folderService;
		$this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    public function index()
    {
        $input = Request::all();
        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $formula = $this->service->get($input['job_id'], $input);

        if (!$limit) {
            return ApiResponse::success(
                $this->response->collection($formula, new MeasurementTransformer)
            );
        }
        return ApiResponse::success(
            $this->response->paginatedCollection($formula, new MeasurementTransformer)
        );
    }

    public function store()
    {
        $input = Request::all();
        $rules = array_merge(Measurement::getRules(), MeasurementValue::getRules());
		$validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $measurement = $this->service->saveMeasurement(
                $input['job_id'],
                $input['title'],
                $input['values'],
                $input
            );
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Measurement']),
                'data' => $this->response->item($measurement, new MeasurementTransformer)
            ]);
        }  catch(FolderNotExistException $e) {
            DB::rollBack();
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function update($id)
    {
        $input = Request::all();
        $rules = array_merge(Measurement::getRules($id), MeasurementValue::getRules());
		$validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $measurement = $this->service->getMeasurementById($id);
        DB::beginTransaction();
        try {
            $measurement = $this->service->updateMeasurement(
                $measurement,
                $input['title'],
                $input['values'],
                $input
            );
            DB::commit();

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Measurement']),
                'data' => $this->response->item($measurement, new MeasurementTransformer)
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function show($id)
    {
        try {
            $measurement = $this->service->getMeasurementDetailsById($id);

            return ApiResponse::success(['data' => $this->response->item($measurement, new MeasurementTransformer)]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function destroy($id)
    {
       try {
            $measurement = $this->service->getMeasurementById($id);
            if(!$measurement->file_path && !$measurement->file_mime_type) {
                return ApiResponse::errorGeneral([
                    'message' => 'you are unable to delete this measurement'
                ]);
            }
            $measurement->delete();
            $message = trans('response.success.deleted', ['attribute' => 'Measurement']);
            Event::fire('JobProgress.Templates.Events.Folder.deleteFile', new JobMeasurementDeleteFile($id));
            return ApiResponse::success([
                'message' => $message
            ]);
        } catch(\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
    * rename measurement
    *
    * @param $id (measurement id)
    * @param title
    * @return success
    */
    public function rename($id) {
        $input = Request::onlyLegacy('title');
        $validator = Validator::make($input,['title' => 'required']);
        if($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $this->service->rename($id, $input['title']);
            return ApiResponse::success([
                'message' => trans('response.success.rename',['attribute' => 'Measurement'])
            ]);
        } catch(\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
    /**
    * update hover measurement in measurement from hover report
    *
    * @return success response
    */
    public function updateHoverMeasurement()
    {
        try {
            $measurementId = Request::get('measurement_id');
            $filePath = config('jp.BASE_PATH').'hover_reports/1549358486_1763812421_reports.pdf';
            $measurement = $this->service->getMeasurementById($measurementId);
            $data = $this->service->updateHoverMeasurement($measurement, $filePath);
            return ApiResponse::success([
                'message' => trans('response.success.updated',['attribute' => 'Measurement'])
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
    * update wastefactor in measurement
    *
    * @return success response
    */
    public function updateMeasurementValue($id)
    {
        $input = Request::onlyLegacy('value', 'attribute_id');
        $measurement = $this->service->getMeasurementById($id);
        $validator = Validator::make($input, [
            'value'        => 'required',
            'attribute_id' => 'required'
        ]);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $attribute = $this->service->getAtributeById($input['attribute_id']);
		$measurementValue = MeasurementValue::firstOrNew([
			'attribute_id'   => $attribute->id,
			'measurement_id' => $id,
		]);
		$measurementValue->trade_id = $attribute->trade_id;
		$measurementValue->value = $input['value'];
        $measurementValue->save();

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => $attribute->name]),
        ]);
    }

    /**
	* upload file in measurement
	*
	* @return success response
	*/
	public function fileUpload()
	{
		$input = Request::onlyLegacy('job_id', 'file', 'make_pdf', 'image_base_64', 'rotation_angle', 'title', 'parent_id');
		$validator = Validator::make($input, Measurement::getFileUploadRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		if(!$input['image_base_64'] && !(Request::hasFile('file'))){
			return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'file']));
		}

		try{
			$measurement = $this->service->uploadFile(
				$input['job_id'],
				$input['file'],
				$input['image_base_64'],
				$input
			);

			return ApiResponse::success([
				'message' => trans('response.success.file_uploaded'),
				'data'    => $this->response->item($measurement, new MeasurementTransformer)
			]);

		} catch(InvalidFileException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
		} catch(FolderNotExistException $e) {
			return ApiResponse::errorGeneral($e->getMessage());
		} catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	public function rotateImageFile($id)
	{
		$input = Request::onlyLegacy('rotation_angle');
		$validator = Validator::make($input,['rotation_angle' => 'numeric'] );
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		if($input['rotation_angle'] == '') {
			return ApiResponse::errorGeneral(trans('response.error.roation_angle_required'));
		}

		try{
			$measurement = $this->service->getMeasurementById($id);
			if(!in_array($measurement->file_mime_type,config('resources.image_types'))) {

				return ApiResponse::errorGeneral(trans('response.error.only_image_rotate'));
			}
			$measurement = $this->service->rotateImage($measurement, $input['rotation_angle']);
			return ApiResponse::success([
				'message' => trans('response.success.rotated', ['attribute' => 'Image']),
				'data'    => $this->response->item($measurement, new MeasurementTransformer)
			]);
		} catch (ModelNotFoundException $e) {
			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Measurement']));
		}
		catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * Download File
	 * Get /measurements/{id}/download
	 * @param  Int $id    Measurement id
	 * @return File
	 */
	public function download($id)
	{
		try{
			$measurement = $this->service->getMeasurementById($id);
			$fullPath = config('jp.BASE_PATH').$measurement->file_path;

			return FlySystem::download($fullPath, $measurement->file_name);
		} catch (ModelNotFoundException $e) {
			return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Measurement']));
		}
		catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

    /**
	 * Create folder in measurements.
	 *
	 * POST - /measurements/folder
	 * @return json response.
	 */
	public function createFolder()
	{
		$inputs = Request::all();
		$validator = Validator::make($inputs, Measurement::getFolderRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$job = $this->jobRepo->getById($inputs['job_id']);

		try {
			$item = $this->folderService->createMeasurementFolder($inputs);

			return ApiResponse::success([
				'data' => $this->response->item($item, new MeasurementTransformer)
			]);
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
