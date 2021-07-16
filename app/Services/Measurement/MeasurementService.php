<?php

namespace App\Services\Measurement;

use App\Models\Company;
use App\Models\Measurement;
use App\Models\MeasurementFormula;
use App\Models\MeasurementAttribute;
use App\Models\Trade;
use App\Repositories\MeasurementRepository;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Services\EagleView\EagleView;
use App\Events\EagleViewTokenExpired;
use App\Models\EVReport;
use DataMasking;
use PDF;
use FlySystem;
use App\Models\MeasurementValue;
use Settings;
use App\Exceptions\InvalidFileException;
use Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use App\Services\FileSystem\FileService;
use App\Events\Folders\JobMeasurementStoreFile;
use App\Services\Folders\Helpers\JobMeasurementsQueryBuilder;
use App\Services\Folders\FolderService;
use App\Models\Folder;
use Illuminate\Support\Facades\DB;
use App\Services\Measurement\HoverMeasurementService;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\MeasurementValueTransformer;
use App\Services\EagleView\EagleViewMeasurementService;

class MeasurementService
{
    public function __construct(
        Context $scope,
        MeasurementRepository $repo,
        EagleView $evService,
        FileService $fileService,
        JobMeasurementsQueryBuilder $jobMeasurementsQueryBuilder,
        HoverMeasurementService $hoverMeasurementService,
		Larasponse $response,
		EagleViewMeasurementService $evMeasurementService
    ){
        $this->scope     = $scope;
        $this->repo      = $repo;
        $this->evService = $evService;
        $this->fileService = $fileService;
        $this->jobMeasurementsQueryBuilder 	 = $jobMeasurementsQueryBuilder;
        $this->hoverMeasurementService 	 = $hoverMeasurementService;
        $this->response 	             = $response;
        $this->evMeasurementService 	 = $evMeasurementService;
    }

    /**
	 * get measurements
	 *
	 * @param $jobId, $filter
	 * @return collection
	 */
	public function get($jobId, $filters = array())
	{
		$builder = $this->repo->listing($jobId, $filters);
		return $this->getMeasurementsAlongWithFolders($builder, $filters);
	}

	/**
     * Query on measurements table to get measurements.
     * and also create query to get Folders along with measurements.
     *
     * @param Eloquent $builder: Eloquent query builder.
     * @param Array $filters: array of filtering parameters.
     * @return Collection of Eloquent model instance.
     */
    public function getMeasurementsAlongWithFolders($builder, $filters = [])
	{
		/* $service = $this->jobMeasurementsQueryBuilder->setBuilder($builder)
										->setFilters($filters)
										->bind();
		$templates = $service->get(); */
		$limit = isset($filters['limit']) ? $filters['limit'] : config('jp.pagination_limit');
		if(!$limit) {
            $templates = $builder->get();
        } else {
            $templates = $builder->paginate($limit);
        }
		return $templates;
	}

    /**
     * get measurements
     *
     * @param $jobId, $filter
     * @return collection
     */
    public function listing($jobId, $filters = [])
    {
        return $this->repo->listing($jobId, $filters);
    }

    public function saveMeasurement($jobId, $title, $values, $meta = array())
    {
        $measurement = $this->repo->save($jobId, $title, $values, Auth::id());
        $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
		if($parentId) {
			$folderService = app(FolderService::class);
			$parentDir = $folderService->getParentDir($parentId, Folder::JOB_MEASUREMENT);
		}
		$eventData = [
			'reference_id' => $measurement->id,
			'name' => $measurement->id,
			'job_id' => $jobId,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMeasurementStoreFile($eventData));

        DataMasking::disable();

        $measurement = $this->createPdf($measurement);
        $measurement = $this->createThumb($measurement);

        // create pdf for sub contractor
        DataMasking::enable();
        $this->createPdf($measurement, true);

        //for getting measurement detail
        if(ine($meta, 'includes') && in_array('measurement_details', $meta['includes'])) {
            $measurement = $this->getMeasurementDetailsById($measurement->id);
        }

        return $measurement;
    }

    /**
     * Update eagleview measurement
     * @param  Instance $measurement Measurement
     * @param  String   $filePath    File Path
     * @return boolean
     */
    public function updateEagleviewMeasurement($measurement,  $filePath, $fileTypeId = null)
    {
        $this->evMeasurementService->updateMeasurement($measurement,  $filePath, $fileTypeId);
    }

    /**
     * update measurement create pdf and create thumb
     *
     * @param $measurement, $title, $values
     * @return measurement
     */
    public function updateMeasurement($measurement, $title, $values, $meta = array())
    {
        $measurement = $this->repo->update($measurement, $title, $values);
        $subMeasurementOldPath = $measurement->file_path;

        DataMasking::disable();
        $measurement = $this->createPdf($measurement);
        $measurement = $this->createThumb($measurement);
        DataMasking::enable();

        // create pdf for sub contractor
        $this->createPdf($measurement, true, $subMeasurementOldPath);
        //for getting measurement detail
        if(ine($meta, 'includes') && in_array('measurement_details', $meta['includes'])) {
            $measurement = $this->getMeasurementDetailsById($measurement->id);
        }
        return $measurement;
    }

    /**
     * get measurement by id
     *
     * @param $id (measurement id)
     * @return measurement
     */
    public function getMeasurementById($id)
    {
        $measurement = $this->repo->getById($id);
        return $measurement;
    }

    /**
     * get measurement details by id
     *
     * @param $id (measurement id)
     * @return measurement
     */
    public function getMeasurementDetailsById($id)
    {
        $measurement = $this->repo->getById($id);
        $company = Company::find($this->scope->id());
        $companyId = $company->id;

        $measurementTrade = $measurement->values()->pluck('trade_id')->toArray();

        $trades = Trade::whereIn('trades.id', $measurementTrade)->with([
			'measurementValues' => function($query) use($id, $measurementTrade) {
				$query->leftJoin(
					DB::raw("(SELECT * FROM measurement_values where measurement_id=$id) as measurement_values"),
					'measurement_values.attribute_id', '=', 'measurement_attributes.id'
				)
				->select('measurement_attributes.*', 'measurement_values.value', 'measurement_values.index')
				->excludeDeletedAndInactiveAttributesWithoutValues($id, $measurementTrade);
			},
			'measurementValues.subAttributeValues' => function($query) use($id, $measurementTrade) {
				$query->leftJoin(
					DB::raw("(SELECT * FROM measurement_values where measurement_id=$id) as measurement_values"),
					'measurement_values.attribute_id', '=', 'measurement_attributes.id'
				);
				$query->select('measurement_attributes.*', 'measurement_values.value', 'measurement_values.index')
				->excludeDeletedAndInactiveAttributesWithoutValues($id, $measurementTrade);
			},
			'measurementValuesSummary' => function($query) use($id, $measurementTrade) {
				$query->leftJoin(
					DB::raw("(SELECT * FROM measurement_values where measurement_id=$id) as measurement_values"),
					'measurement_values.attribute_id', '=', 'measurement_attributes.id'
				)
				->select('measurement_attributes.*', DB::raw('SUM(measurement_values.value) as value'))
				->excludeDeletedAndInactiveAttributesWithoutValues($id, $measurementTrade)
				->groupBy('measurement_attributes.id');
			},
			'measurementValuesSummary.subAttributeValuesSummary' => function($query) use($id, $measurementTrade) {
				$query->leftJoin(
					DB::raw("(SELECT * FROM measurement_values where measurement_id=$id) as measurement_values"),
					'measurement_values.attribute_id', '=', 'measurement_attributes.id'
				)
				->select('measurement_attributes.*', DB::raw('SUM(measurement_values.value) as value'))
				->excludeDeletedAndInactiveAttributesWithoutValues($id, $measurementTrade)
				->groupBy('measurement_attributes.id');
			},
			'measurementValues.unit',
			'measurementValues.subAttributeValues.unit',
			'measurementValuesSummary.unit',
			'measurementValuesSummary.subAttributeValuesSummary.unit',
		])
		->groupBy('trades.id')
		->select('trades.*')
		->get();

        $transformer = new MeasurementValueTransformer;
        $transformer->setDefaultIncludes(['sub_attribute_values', 'unit']);

		// transform measurement values according to the index
		$return = [];
		foreach ($trades as $key => $trade) {
			$measurementValues = $trade->measurementValues->groupBy('index');

			// Set attributes that doesn't have any value at all the indexes
			if(isset($measurementValues[''])) {
				$newAttributeValues = $measurementValues[''];
				unset($measurementValues['']);
				foreach (arry_fu($measurementValues->keys()) as $index) {
					$measurementValues[$index] = array_merge($measurementValues[$index], $newAttributeValues);
				}
			}

			// Sort by company id so that superadmin attributes will always be on top
			foreach ($measurementValues as $index => $measurementValue) {
				$companyAttributes = [];
				$systemAttributes = [];
				foreach ($measurementValue as $key => $value) {
					if($value->company_id > 0) {
						$companyAttributes[] = $value;
					}else {
						$systemAttributes[] = $value;
					}
				}

				$measurementValues[$index] = array_merge($systemAttributes, $companyAttributes);
			}

			$transformedValues = [];
			foreach ($measurementValues as $key => $value) {
				$transformedValues[$key] = $this->response->collection($value, $transformer)['data'];
			}

			ksort($transformedValues, SORT_ASC);

			$trade->measurementValues = $transformedValues;
			$return[] = $trade;
		}

		$measurement->trades = $return;

		return $measurement;
    }

    /**
    *  pdf and thumb generation for measurement
    *
    * @return measurement
    */
    public function createPdf(Measurement $measurement, $subMeasurement = false, $subMeasurementOldPath = null)
    {
        $existingFile = null;
        if(!empty($measurement->file_path)) {
            $existingFile = config('jp.BASE_PATH').$measurement->file_path;
        }
        $filename  = $measurement->id.'_'.Carbon::now()->timestamp.rand().'.pdf';
        $baseName = 'measurements/' . $filename;
        $fullPath = config('jp.BASE_PATH').$baseName;
        $pageHeight = '23.9cm';
        if($measurement->page_type == 'legal-page') {
            $pageHeight = '28.6cm';
        }
        $job = $measurement->job;
        $measurementDetail = $this->getMeasurementDetailsById($measurement->id);
        if($subMeasurement) {
            $baseName = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $measurement->file_path);
            $fullPath = config('jp.BASE_PATH').$baseName;
            $pdf = PDF::loadView('jobs.measurement', [
                'job'         => $job,
                'company'     => $job->company,
                'customer'    => $job->customer,
                'measurement' => $measurementDetail,
                'country'     => $job->company->country,
            ])
            ->setOption('dpi', 200)
            ->setOption('page-size','A4')
            ->setOption('page-width','16.8cm')
            ->setOption('page-height', $pageHeight);
            $mimeType = 'application/pdf';
            FlySystem::put($fullPath, $pdf->output(), ['ContentType' => $mimeType]);
            // delete sub-contractor's existing measurement Pdf
            if($subMeasurementOldPath) {
                FlySystem::delete(preg_replace('/(\.pdf)/i', '_sub_contractor$1', config('jp.BASE_PATH').$subMeasurementOldPath));
            }
            return $measurement;
        }
        $pdf = PDF::loadView('jobs.measurement', [
                'job'         => $job,
                'company'     => $job->company,
                'customer'    => $job->customer,
                'measurement' => $measurementDetail,
                'country'     => $job->company->country,
            ])
            ->setOption('dpi', 200)
            ->setOption('page-size','A4')
            ->setOption('page-width','16.8cm')
            ->setOption('page-height', $pageHeight);
        $mimeType = 'application/pdf';
        FlySystem::put($fullPath, $pdf->output(), ['ContentType' => $mimeType]);
        $measurement->file_name = $measurement->title.'.pdf';
        $measurement->file_path = $baseName;
        $measurement->file_mime_type = $mimeType;
        $measurement->file_size = FlySystem::getSize($fullPath);
        $measurement->save();
        // delete existing Pdf
        if(!is_null($existingFile)) {
            FlySystem::delete($existingFile);
        }
        return $measurement;
    }

    /**
    * Rename measurement
    *
    * @param $id ,$title
    * @return measurement
    */
    public function rename($id , $title)
    {
        $measurement = $this->getMeasurementById($id);
        $measurement->title = $title;
        $measurement->save();
        return $measurement;
    }

    /**
     * Update hover measurement
     * @param  Instance $measurement Measurement
     * @param  String   $filePath    File Path
     * @return boolean
     */
    public function updateHoverMeasurement($measurement,  $filePath)
    {
        $this->hoverMeasurementService->updateMeasurementFromJson($measurement, $filePath);
    }

    /**
	 * Get attribute by id
	 * @param  int $id    Attribute Id
	 * @return Attribute Instance
	 */
	public function getAtributeById($id)
	{
		return MeasurementAttribute::where('company_id', getScopeId())->findOrFail($id);
    }

    public function uploadFile($jobId, $file, $imageBase64 = null, $input = array())
	{
        $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
		if($parentId) {
			$folderService = app(FolderService::class);
			$parentDir = $folderService->getParentDir($parentId, Folder::JOB_MEASUREMENT);
		}
		if($imageBase64 && !is_file($file)) {
			return $this->uploadBase64($file, $jobId, $input);
		}

		$originalName = $file->getClientOriginalName();
		$mimeType = $file->getMimeType();

		//scanner
		if (ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
			$originalName 	= substr($originalName, 0, strpos($originalName, '.')).'.pdf';
			$mimeType		= 'application/pdf';
			$physicallyName = generateUniqueToken().'_'.str_replace(' ', '_', strtolower($originalName));
			$basePath 	= 'measurements/'.$physicallyName;
			$fullPath 	= config('jp.BASE_PATH').$basePath;
			$imgContent	= base64_encode(file_get_contents($file));

			$data = [
				'imgContent' => $imgContent,
			];

			$content = view('resources.single_img_as_pdf', $data)->render();

			$pdf = PDF::loadHTML($content)->setPaper('a4')->setOrientation('portrait');
			$pdf->setOption('dpi', 200);

			$uploaded = FlySystem::put($fullPath, $pdf->output(), ['ContentType' => $mimeType]);
			$fileSize = FlySystem::getSize($fullPath);
		} else {
			$physicallyName = generateUniqueToken().'_'.str_replace(' ', '_', strtolower($originalName));
			$basePath = 'measurements/'.$physicallyName;
			$fullPath = config('jp.BASE_PATH').$basePath;
			$fileSize = $file->getSize();

			//upload file..
			if (in_array($mimeType, config('resources.image_types'))) {
				$image = Image::make($file)->orientate();

				if(isTrue(Settings::get('WATERMARK_PHOTO'))) {
					$image = addPhotoWatermark($image, $jobId);
				}

				$uploaded = FlySystem::put($fullPath, $image->encode()->getEncoded(), ['ContentType' => $mimeType]);
			} else {
				$uploaded = FlySystem::writeStream($fullPath, $file, ['ContentType' => $mimeType]);
			}
		}

		// save thumb for images..
		$thumbBasePath = null;
		if(!ine($input, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
			$thumbBasePath  = 'measurements/thumb/' . $physicallyName;
			$thumbPath = config('jp.BASE_PATH'). $thumbBasePath;

			if($image->height() > $image->width()) {
				$image->heighten(200, function($constraint) {
			    	$constraint->upsize();
			   	});
			}else {
			    $image->widen(200, function($constraint) {
			       $constraint->upsize();
			    });
			}

			FlySystem::put($thumbPath, $image->encode()->getEncoded());
		}

		$createdBy      = Auth::id();
		$input['title'] = $originalName;

		//add file data..
		$input['is_file']		 = true;
		$input['file_name']		 = $originalName;
		$input['file_path']		 = $basePath;
		$input['file_mime_type'] = $mimeType;
		$input['file_size']		 = $fileSize;
		$input['thumb']			 = $thumbBasePath;

		//create measurement..
        $measurement = $this->repo->save($jobId, $input['title'], $values = array(), $createdBy, $input);

        $eventData = [
			'reference_id' => $measurement->id,
			'name' => $measurement->id,
			'job_id' => $jobId,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMeasurementStoreFile($eventData));

		return $measurement;
	}

	public function createFileFromContents($jobId, $fileContent, $name, $mimeType)
	{
		$physicalName = uniqueTimestamp().'_'.str_replace(' ', '_', strtolower($name));
		$basePath = 'measurements/' . $physicalName;
		$fullPath = config('jp.BASE_PATH').$basePath;

		//save file..
	    FlySystem::put($fullPath, $fileContent, ['ContentType' => $mimeType]);

	    $fileSize = FlySystem::getSize($fullPath);

	    // save thumb for images..
		$thumbBasePath = null;
		if(in_array($mimeType, config('resources.image_types'))) {
			$thumbBasePath  = 'measurements/thumb/' . $physicalName;
			$thumbPath = config('jp.BASE_PATH'). $thumbBasePath;

			$image = Image::make($fileContent);
			if($image->height() > $image->width()) {
				$image->heighten(200, function($constraint) {
			    	$constraint->upsize();
			   	});
			}else {
			    $image->widen(200, function($constraint) {
			       $constraint->upsize();
			    });
			}

			FlySystem::put($thumbPath, $image->encode()->getEncoded());
		}

		$createdBy = Auth::id();
		$input['title'] = $name;

		//add file data..
		$input['is_file']		 = true;
		$input['file_name']		 = $name;
		$input['file_path']		 = $basePath;
		$input['file_mime_type'] = $mimeType;
		$input['file_size']		 = $fileSize;
		$input['thumb']			 = $thumbBasePath;

		//create estimation..
        $measurement = $this->repo->save($jobId, $input['title'], $values = array(), $createdBy, $input);

        $eventData = [
			'reference_id' => $measurement->id,
			'name' => $measurement->id,
			'job_id' => $jobId,
			'parent_id' => null,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMeasurementStoreFile($eventData));

		return $measurement;
	}

	public function rotateImage($measurement, $rotateAngle = 0)
	{
		$oldFilePath = $measurement->file_path;
		$oldThumbPath = $measurement->thumb;

		$extension = File::extension($measurement->file_path);
		$physicalName = uniqueTimestamp().'.' . $extension;
		$newFilePath = 'measurements/' . $physicalName;
		$this->rotate($measurement->file_path, $newFilePath, $rotateAngle);

		//rotate thumb
		$newThumbPath = 'measurements/thumb/' . $physicalName;
		$this->rotate($measurement->thumb, $newThumbPath, $rotateAngle);

		//update Measurement with file path
		$measurement->file_path = $newFilePath;
		$measurement->thumb = $newThumbPath;
		$measurement->save();

		if(!empty($oldFilePath)) {
			FlySystem::delete(config('jp.BASE_PATH').$oldFilePath);
		}

		if(!empty($oldThumbPath)) {
			FlySystem::delete(config('jp.BASE_PATH').$oldThumbPath);
		}

		return $measurement;
	}

    /********************** PRIVATE METHODS **************************/
    /**
    * create thumb and image for measurement
    */
    private function createThumb($measurement)
    {
        $job = $measurement->job;
        $measurement = $this->getMeasurementDetailsById($measurement->id);
        $contents = view('jobs.measurement', [
                'measurement' => $measurement,
                'job'         => $job,
                'customer'    => $job->customer,
                'company'     => $job->company,
                'country'     => $job->company->country,
            ])->render();
        $filename  = Carbon::now()->timestamp.rand().'.jpg';
        $imageBaseName = 'measurements/' . $filename;
        $thumbBaseName = 'measurements/thumb/' . $filename;
        $imageFullPath = config('jp.BASE_PATH').$imageBaseName;
        $thumbFullPath = config('jp.BASE_PATH').$thumbBaseName;
        $snappy = App::make('snappy.image');
        $snappy->setOption('width', '794');
        $snappy->setOption('height', '1122');

        $image = $snappy->getOutputFromHtml($contents);
        // save image...
        FlySystem::put($imageFullPath, $image, ['ContentType' => 'image/jpeg']);
        // resize for thumb..
        $image = Image::make($image);
        if($image->height() > $image->width()) {
            $image->heighten(250, function($constraint) {
                $constraint->upsize();
            });
        }else {
            $image->widen(250, function($constraint) {
               $constraint->upsize();
            });
        }
        // save thumb ..
        FlySystem::put($thumbFullPath, $image->encode()->getEncoded());
        unset($measurement->trades);
        $measurement->image = $imageBaseName;
        $measurement->thumb = $thumbBaseName;
        $measurement->save();

        return $measurement;
    }

    private function uploadBase64($data, $jobId, $meta = array())
	{
		$title = "";
        if(isset($meta['title']) && (strlen($meta['title']) > 0)) {
            $title = $meta['title'];
        }

		$name = uniqueTimestamp(). '.jpg';
		$basePath 	= 'measurements/';
		$fullPath 	= config('jp.BASE_PATH').$basePath;
		$thumbName = 'measurements/thumb/'. $name;
		$fullThumbPath = config('jp.BASE_PATH'). $thumbName;

		$rotationAngle = ine($meta, 'rotation_angle') ? $meta['rotation_angle'] : null;
		$file = uploadBase64Image($data, $fullPath, $name, $rotationAngle, true, null, $fullThumbPath);

		if(!$file) {
			throw new InvalidFileException("Invalid File Type");
		}

		$meta['file_name'] 		= $file['name'];
		$meta['file_size'] 		= $file['size'];
		$meta['file_mime_type'] = $file['mime_type'];
		$meta['file_path'] 		= $basePath . '/' . $name;
		$meta['is_file']		= true;
		$meta['thumb'] 			= $thumbName;
		$createdBy      		= Auth::id();

        $measurement = $this->repo->save($jobId, $title, $values = array(), $createdBy, $meta);
        $parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
		$eventData = [
			'reference_id' => $measurement->id,
			'name' => $measurement->id,
			'job_id' => $jobId,
			'parent_id' => $parentId,
		];
		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobMeasurementStoreFile($eventData));

		return $measurement;
	}

	private function rotate($oldFilePath, $newFilePath, $rotateAngle = 0)
	{
		$filePath = config('jp.BASE_PATH').$oldFilePath;
		$basePath = config('jp.BASE_PATH').$newFilePath;
		$img = Image::make(FlySystem::read($filePath));
        $img->rotate($rotateAngle);

        FlySystem::put($basePath, $img->encode()->getEncoded());
	}
}
