<?php
namespace App\Services;

use App\Repositories\ClickThruEstimateRepository;
use App\Repositories\WaterproofingLevelTypeRepository;
use App\Repositories\PredefinedEstimateTypeRepository;
use App\Services\Worksheets\WorksheetsService;
use App\Models\EstimateType;
use App\Models\FinancialProduct;
use App\Models\WarrantyType;
use App\Models\EstimatePitch;
use App\Models\EstimateChimney;
use App\Models\AccessToHome;
use App\Models\EstimateGutter;
use App\Models\FinancialCategory;
use App\Models\Manufacturer;
use FlySystem;
use App\Models\Estimation;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use App\Models\Job;
use App\Models\EstimateTypeLayer;
use App\Models\Waterproofing;
use App\Models\EstimateLevel;
use App\Models\EstimateStructure;
use App\Models\EstimateVentilation;
use App\Services\Emails\EmailServices;
use PDF;
use Illuminate\Support\Facades\Auth;

class ClickThruEstimate {
    function __construct(
    	ClickThruEstimateRepository $repo,
    	WaterproofingLevelTypeRepository $levelRepo,
  	    PredefinedEstimateTypeRepository $typeRepo,
    	WorksheetsService $worksheetService,
    	EmailServices $emailService
    ){
        $this->repo = $repo;

		$this->levelRepo = $levelRepo;
		$this->typeRepo = $typeRepo;
		$this->worksheetService = $worksheetService;
		$this->emailService = $emailService;
	}
	public function saveEstimate(
		$name,
		$jobId,
		$manufacturerId,
		$typeId,
		$levelId,
		$waterproofingId,
		$shingleId,
		$underlaymentId,
		$warrantyId,
		$roofSize,
		$pitchId,
		$accessToHome,
		$meta
	){
		$job = Job::findOrFail($jobId);
		$manufacturer = Manufacturer::findorFail($manufacturerId);

		$levelData = $this->getLevelData($levelId);
		$typeData = $this->getTypeData($typeId, $meta);
		$waterproofingData = $this->getWaterproofingData($waterproofingId);
		$shingleData = $this->getShingleUnderlaymentData($shingleId, $levelId, $manufacturerId, FinancialProduct::SHINGLES);
		$underlaymentData = $this->getShingleUnderlaymentData($underlaymentId, $levelId, $manufacturerId, FinancialProduct::UNDERLAYMENTS);
		$warrantyData = $this->getWarrantyData($warrantyId, $levelId, $manufacturerId);
		$pitchData = $this->getPitchData($pitchId);
        $data = [];

		if(ine($meta, 'id')){
			$data['id'] = $meta['id'];
		}

        $data['structure'] = ine($meta, 'structure_id') ? $this->getStructureComplexityData($meta['structure_id']) : [];
		$data['complexity'] = ine($meta, 'complexity_id') ? $this->getStructureComplexityData($meta['complexity_id']) : [];
		$data['chimney'] = ine($meta, 'chimney_ids') ? $this->getChimneyData($meta['chimney_ids']) : [];
		$data['others'] = ine($meta, 'ventilations') ? $this->getVentilationData($meta['ventilations']) : [];
		$data['skylight'] = ine($meta, 'skylight') ? $meta['skylight'] : 0;
		$data['access_to_home'] = $this->getAccessToHomeData($accessToHome);
		$data['gutter'] = ine($meta, 'gutter') ? $this->getGutterData($meta['gutter']) : [];
		$data['notes'] = ine($meta, 'notes') ? $meta['notes'] : null;
		$data['adjustable_amount'] = ine($meta, 'adjustable_amount') ? numberFormat($meta['adjustable_amount']) : 0;
		$data['adjustable_note'] = ine($meta, 'adjustable_note') ? $meta['adjustable_note'] : null;
		$data['amount'] = $this->getTotalAmount(
			$levelData['amount'],
			$typeData,
			$waterproofingData,
			$shingleData['per_unit_cost'],
			$underlaymentData['per_unit_cost'],
			$pitchData['fixed_amount'],
			$data['structure'],
			$data['complexity'],
			$data['chimney'],
			$data['others'],
			$data['skylight'],
			$roofSize,
			$data['access_to_home'],
			$data['gutter'],
			$data['adjustable_amount']
		);

        if(ine($meta, 'users')){
			$data['users'] = $meta['users'];
		}

        $estimate = $this->repo->save(
			$name,
			$job->id,
			$job->customer_id,
			$manufacturerId,
			$levelData,
			$typeData,
			$waterproofingData,
			$shingleData,
			$underlaymentData,
			$warrantyData,
			$roofSize,
			$pitchData,
			$data
		);

        $data =  [
			'job' 	                 => $job,
			'company'                => $job->company,
			'customer'               => $job->customer,
			'estimate'               => $estimate,
			'manufacturer'  		 => $manufacturer,
			'level'  				 => $estimate->level,
			'waterproofing'   		 => $estimate->waterproofing,
			'shingle'   			 => $estimate->shingle,
			'underlayment'   		 => $estimate->underlayment,
			'warranty'               => $estimate->warranty,
			'type'           		 => $estimate->type,
			'complexity'	 		 => $estimate->complexity,
			'structure'	 		 	 => $estimate->structure,
			'gutter'	 		 	 => $estimate->gutter,
			'pitch'	 		 	     => $estimate->pitch,
			'accessToHome'	 	 	 => $estimate->access_to_home,
			'chimney'	 		 	 => $estimate->chimney,
			'others'	 		 	 => $estimate->others
        ];

		$contents = view('clickthru.clickthru_estimate', $data)->render();
		$pdfFile = $this->createPdf($contents, $name, $estimate->file);
		$estimate->file = $pdfFile;
		$estimate->save();

        $jobEstimate = $this->saveEstimation($estimate->id, $estimate->name, $estimate->job_id, $estimate->file, $estimate);

		$attachment[] = [
            'type' =>'estimate',
			'value' => $jobEstimate->id
		];

        if(!empty($usersEmail = $estimate->users()->pluck('email')->toArray())){

            $this->sendEmail(arry_fu($usersEmail), $estimate->job, $estimate->customer, $attachment);

		}

        return $jobEstimate;
    }

	public function createEstimateWorksheet($estimate, $name)
	{
		$data = [];
		$data['name'] = $name;
		$data['job_id'] = $estimate->job_id;
		$data['type'] = "estimate";
		$data['title'] = "Estimate";
		$data['clickthru_estimate_id'] = $estimate->id;
		$data['details'] = [];
		$actualSize = ($estimate->roof_size - $estimate->skylight);

        $categories = FinancialCategory::where('company_id', getScopeId())->orderBy('id', 'desc')->get();

        foreach ($categories as $key => $category) {

			if(!$category->name) {
                continue;
            }

			switch ($category->name) {
				case FinancialCategory::LABOR:
				break;
				case FinancialCategory::MISC:
					$details = $this->getMiscDetails($category->id, $actualSize, $estimate);
					$data['details'] = array_merge($details, $data['details']);
				break;
				case FinancialCategory::ACTIVITY:
					$details = $this->getActivityDetails($category->id, $actualSize, $estimate->type);
					$data['details'] = array_merge($details, $data['details']);
				break;
				case FinancialCategory::MATERIALS:
					$details = $this->getMaterialsDetails($category->id, $actualSize, $estimate->shingle, $estimate->underlayment, $estimate->gutter);
					$data['details'] = array_merge($details, $data['details']);
				break;
				case FinancialCategory::NO_CHARGE:
				break;
			}
		}
		$worksheet = $this->worksheetService->createOrUpdateWorksheet($data);

        return $worksheet;
    }

	private function getLevelData($levelId)
	{
		$level = $this->levelRepo->getLevelById($levelId);
		$data = [];
		$data['id'] = (int)$levelId;
		$data['name'] = $level->type;
		$data['amount'] = numberFormat($level->fixed_amount);

        return $data;
	}

    private function getTypeData($typeId, $meta)
	{
		$data = [];
		$type = EstimateType::findOrFail($typeId);
		$data['id'] = $typeId;
		$data['name'] = $type->name;

        if(!ine($meta, 'layer_id') || $type->name == 'Roof Over'){
            return $data;
        }

		$layer = $this->typeRepo->getLayersById($meta['layer_id']);
		$data['layer_id'] = (int)$meta['layer_id'];
		$data['layers'] = $layer->layers;
		$data['amount'] = numberFormat($layer->cost);
		$data['amount_type'] = $layer->cost_type;

        return $data;
	}

    private function getWaterproofingData($waterproofingId)
	{
		$waterproofing = $this->levelRepo->getWaterproofingById($waterproofingId);
		$data = [];
		$data['id'] = (int)$waterproofingId;
		$data['name'] = $waterproofing->type;
		$data['amount'] = numberFormat($waterproofing->cost);
		$data['amount_type'] = $waterproofing->cost_type;

        return $data;
	}

    private function getShingleUnderlaymentData($productId, $levelId, $manufacturerId, $type)
	{
		$product = FinancialProduct::join('shingles_underlayments',function($join) use($type, $levelId, $manufacturerId){
				$join->on('financial_products.id', '=', 'shingles_underlayments.product_id')
				->where('shingles_underlayments.company_id', '=', getScopeId())
				->where('shingles_underlayments.manufacturer_id', '=', $manufacturerId)
				->where('shingles_underlayments.type', '=', $type)
				->where('shingles_underlayments.level_id', '=', $levelId);
            })->where('financial_products.company_id', getScopeId())
            ->select(
                'financial_products.*',
                'shingles_underlayments.conversion_size as conversion_size'
            )->findOrFail($productId);

        $conversionSize = ($product->conversion_size <= 0) ?  1 : $product->conversion_size;
		$data = [];
		$data['id'] = (int)$productId;
		$data['name'] = $product->name;
		$data['conversion_size'] = $conversionSize;
		$data['unit'] = $product->unit;
		$data['unit_cost'] = numberFormat($product->unit_cost);
		$data['selling_price'] = numberFormat($product->selling_price);
		$data['per_unit_cost'] = numberFormat($product->unit_cost / $conversionSize);
		$data['description'] = $product->description;

        return $data;
	}

    private function getWarrantyData($warrantyId, $levelId, $manufacturerId)
	{
		$warranty = WarrantyType::where('warranty_types.company_id', getScopeId())
			->where('warranty_types.manufacturer_id', $manufacturerId)
			->join('warranty_type_levels', function($join) use($levelId){
				$join->on('warranty_types.id', '=', 'warranty_type_levels.warranty_id')
					->where('warranty_type_levels.level_id', '=', $levelId);
			})->select('warranty_types.*')
            ->findorFail($warrantyId);

		$data = [];
		$data['id'] = (int)$warrantyId;
		$data['name'] = $warranty->name;
		$data['description'] = $warranty->description;

        return $data;
	}

    private function getStructureComplexityData($id)
	{
		$structure = $this->typeRepo->getStructureById($id);
		$data = [];
		$data['type_id'] = (int)$id;
		$data['name'] = $structure->name;
		$data['amount'] = numberFormat($structure->amount);
		$data['amount_type'] = $structure->amount_type;

        return $data;
	}

    private function getPitchData($pitchId)
	{
        $pitch = EstimatePitch::where('company_id', getScopeId())
            ->findOrFail($pitchId);

		$data = [];
		$data['id'] = (int)$pitchId;
		$data['name'] = $pitch->name;
		$data['fixed_amount'] = numberFormat($pitch->fixed_amount);

        return $data;
	}

    private function getChimneyData($chimneyIds)
	{
		$data = [];

        foreach ($chimneyIds as $key => $chimneyId) {
            $chimney = EstimateChimney::where('company_id', getScopeId())
                ->findOrFail($chimneyId);

			$data[$key]['id'] = (int)$chimneyId;
			$data[$key]['size'] = $chimney->size;
			$data[$key]['total_amount'] = numberFormat($chimney->amount);
			$data[$key]['arithmetic_operation'] = $chimney->arithmetic_operation;
        }

		return $data;
	}

    private function getVentilationData($ventilations)
	{
		$data = [];

        foreach ($ventilations as $key => $ventilation){
			$ventilationId = ine($ventilation, 'id') ? $ventilation['id'] : 0;
			$ventilationData = $this->typeRepo->getVentilationById($ventilationId);

            $data[$key]['id'] = (int)$ventilationId;
			$data[$key]['count'] = ine($ventilation, 'count') ? (int)$ventilation['count'] : 0;
			$data[$key]['type'] = $ventilationData->type;
			$data[$key]['amount'] = numberFormat($ventilationData->fixed_amount);
			$data[$key]['arithmetic_operation'] = $ventilationData->arithmetic_operation;
			$data[$key]['total_amount'] =  numberFormat($data[$key]['count']*$ventilationData->fixed_amount);
		}

        return $data;
	}

    private function getAccessToHomeData($type)
	{
		$data = [];
		$data['type'] = $type;
		$accessToHome = AccessToHome::where('company_id', getScopeId())
            ->where('type', $type)
            ->first();

		if(!$accessToHome) {
            return $data;
        }

		$data['amount'] =  numberFormat($accessToHome->amount);

        return $data;
	}

    private function getGutterData($gutter)
	{
        $data = [];

		if(!ine($gutter, 'id')) {
            return $data;
        }

		$data['id'] = (int)$gutter['id'];
		$data['type'] = ine($gutter,'type') ? $gutter['type'] : null;

        $gutterData = EstimateGutter::where('company_id', getScopeId())
            ->findOrFail($gutter['id']);

		$data['total_size'] = ine($gutter,'total_size') ? numberFormat($gutter['total_size']) : 0;
		$data['protection_size'] = ine($gutter,'protection_size') ? numberFormat($gutter['protection_size']) : 0;
		$data['amount'] = numberFormat($gutterData->amount);
		$data['protection_amount'] = numberFormat($gutterData->protection_amount);
		$data['total_amount'] = numberFormat(($data['total_size'] * $data['amount']) + ($data['protection_size'] * $data['protection_amount']));

        return $data;
    }

	private function getTotalAmount(
		$levelAmount,
		$type,
		$waterproofing,
		$shingleCost,
		$underlaymentCost,
		$pitchAmount,
		$structure,
		$complexity,
		$chimney,
		$others,
		$skylightSize,
		$roofSize,
		$accessToHome,
		$gutter
	){
		$actualSize = $roofSize - $skylightSize;
		$shingleAmount = numberFormat($actualSize * $shingleCost);
		$underlaymentAmount = numberFormat($actualSize * $underlaymentCost);
		$waterproofingAmount = $this->getTypeAmount($waterproofing, $actualSize);
		$typeAmount = $this->getTypeAmount($type, $actualSize);
		$structureAmount = $this->getTypeAmount($structure, $actualSize);
		$complexityAmount = $this->getTypeAmount($complexity, $actualSize);
		$chimneyAmount = $this->getAmount($chimney);
		$othersAmount = $this->getAmount($others);
		$accessToHomeAmount = ine($accessToHome, 'amount') ? $accessToHome['amount'] : 0;
		$gutterAmount = ine($gutter, 'total_amount') ? $gutter['total_amount']: 0;
		$totalAmount = ($levelAmount + $typeAmount + $waterproofingAmount + $pitchAmount + $shingleAmount + $underlaymentAmount + $structureAmount + $complexityAmount + $chimneyAmount + $othersAmount + $accessToHomeAmount + $gutterAmount);

        return $totalAmount;
	}

    private function getTypeAmount($type, $actualSize)
	{
		$amount = 0 ;

        if(ine($type,'amount') && ine($type, 'amount_type')) {
			$amount = $type['amount'];

            if($type['amount_type'] == 'per_sq_feet'){
				$amount = ($actualSize * $type['amount']);
			}
		}

        return numberFormat($amount);
	}

    private function getAmount($types)
	{
		$amount = 0;

        foreach ($types as $type) {

            if($type['arithmetic_operation'] == 'subtraction') {
				$amount -= $type['total_amount'];
			}else {
				$amount += $type['total_amount'];
			}
        }

		return numberFormat($amount);
	}

    private function getMaterialsDetails($categoryId, $actualSize, $shingle, $underlayment, $gutter)
	{
		$data = [];

        //shingle details
		$data[] = $this->getDetails($categoryId, $shingle['id'], $shingle['name'], $shingle['description'], $actualSize, $shingle['per_unit_cost'], $shingle['selling_price']);

        //underlayment details
		$data[] = $this->getDetails($categoryId, $underlayment['id'], $underlayment['name'], $underlayment['description'], $actualSize, $underlayment['per_unit_cost'], $underlayment['selling_price']);

        //gutter details
		if(ine($gutter, 'type')){

            if($gutter['type'] == 'install_new'){
				$data[] = $this->getDetails($categoryId, null, 'gutter', 'install new', $gutter['total_size'], $gutter['amount']);
			}

            if($gutter['type'] == 'with_protection'){
				$data[] = $this->getDetails($categoryId, null, 'gutter', 'install new', $gutter['total_size'], $gutter['amount']);
				$data[] = $this->getDetails($categoryId, null, 'gutter', 'gutter protections', $gutter['protection_size'], $gutter['protection_amount']);
			}
		}

        return $data;
	}

    private function getMiscDetails($categoryId, $actualSize, $estimate)
	{
		$data = [];

        //warranty details
		$warranty = $estimate->warranty;
		$level = $estimate->level;
		$data[] = $this->getDetails($categoryId, null, $warranty['name'], $warranty['description'], 1, $level['amount']);

        //waterproofing details
		$waterproofing = $estimate->waterproofing;
		$qty = ($waterproofing['amount_type'] == 'fixed') ? 1 : $actualSize;
		$data[] = $this->getDetails($categoryId, null, 'waterproofing', $waterproofing['name'], $qty, $waterproofing['amount']);

        //pitch details
		$pitch = $estimate->pitch;
		$data[] = $this->getDetails($categoryId, null, 'pitch', $pitch['name'], 1, $pitch['fixed_amount']);

        //access to home details
		$accessToHome = $estimate->access_to_home;
		$amount = ine($accessToHome, 'amount') ? $accessToHome['amount'] : 0;
		$data[] = $this->getDetails($categoryId, null, 'access to home',  $accessToHome['type'], 1, $amount);

        //structure details
		$structure = $estimate->structure;

        if(ine($structure, 'name')) {
			$qty = ($structure['amount_type'] == 'fixed') ? 1 : $actualSize;
			$data[] = $this->getDetails($categoryId, null, 'structure', $structure['name'], $qty, $structure['amount']);
        }

		//complexity details
		$complexity = $estimate->complexity;

        if(ine($complexity, 'name')){
			$qty = ($complexity['amount_type'] == 'fixed') ? 1 : $actualSize;
			$data[] = $this->getDetails($categoryId, null, 'complexity', $complexity['name'], $qty, $complexity['amount']);
		}

        //adjustable amount details

        if($estimate->adjustable_amount){
			$data[] = $this->getDetails($categoryId, null, 'adjustable amount', $estimate->adjustable_note, 1, $estimate->adjustable_amount);
		}

        $chimnies = $estimate->chimney;

        if(is_array($chimnies) && !empty($chimnies)){
			foreach ($chimnies as $chimney) {
				$amount = ($chimney['arithmetic_operation'] == 'subtraction') ? -$chimney['total_amount'] : $chimney['total_amount'];
				$data[] = $this->getDetails($categoryId, null, 'chimney', $chimney['size'], 1, $amount);
			}
        }

		$others = $estimate->others;

        if(is_array($others) && !empty($others)){
			foreach ($others as $other) {
				$amount = ($other['arithmetic_operation'] == 'subtraction') ? -$other['amount'] : $other['amount'];
				$data[] = $this->getDetails($categoryId, null, 'others', $other['type'], $other['count'], $amount);
			}
        }

		return $data;
	}

    private function getActivityDetails($categoryId, $actualSize, $layer)
	{
		$data = [];

        if(!ine($layer, 'layer_id') || $layer['name'] == 'Roof Over') {
            return $data;
        }

		$qty = ($layer['amount_type'] == 'fixed') ? 1 : $actualSize;

        //layer details
		$data[] = $this->getDetails($categoryId, null, $layer['name'], $layer['layers'], $qty, $layer['amount']);

        return $data;
	}

    private function getDetails($categoryId, $productId, $productName, $description, $qty, $unitPrice, $sellingPrice = null)
	{
		$data= [];
		$data['category_id'] = $categoryId;
		$data['product_id'] = $productId;
		$data['product_name'] = $productName;
		$data['description'] = $description;
		$data['quantity'] = $qty;
		$data['unit_cost'] = $unitPrice;
		$data['selling_price'] = $sellingPrice;
		$data['tier1'] = null;
		$data['tier2'] = null;
		$data['unit'] = 'Units';
		$data['manualQuantity'] = $qty;
		$data['grand_total'] = ($qty * $unitPrice);

        return $data;
	}

    /**
	 * Save as attachment
	 *
	 * @param  $pdfObject [pdf object of estimate]
	 * @param  $name [pdf name]
	 * @return
	 */
	private function createPdf($contents, $name, $file = [])
	{
		$pdf = PDF::loadHTML($contents)
			->setOption('page-size','A4')
			->setOption('margin-left',0)
			->setOption('margin-right',0)
            ->setOption('dpi', 200);

		$name = preg_replace('/\s+/', '_', $name);
		$companyId = getScopeId();
		$baseName 	   = 'clickthru_estimate/pdf';
		$rootPath	   = config('jp.BASE_PATH').$baseName;
		$physicalName  = $companyId.'_'.Carbon::now()->timestamp.'_'.$name.'.pdf';
		$thumbName     = $companyId.'_'.Carbon::now()->timestamp.'_'.$name.'.jpg';
		$filePath	   = $rootPath.'/'.$physicalName;
		$thumbFilePath	   = $rootPath.'/'.$thumbName;
		$mimeType 	   = 'application/pdf';

        /* save pdf */
		FlySystem::put($filePath, $pdf->output(), ['ContentType' => $mimeType]);

        // create thumb
		$snappy = App::make('snappy.image');
		$image = $snappy->getOutputFromHtml($contents);

        $image = \Image::make($image);

        if($image->height() > $image->width()) {
			$image->heighten(250, function($constraint) {
		    	$constraint->upsize();
		   	});
		}else {
		    $image->widen(250, function($constraint) {
		       $constraint->upsize();
		    });
		}

        FlySystem::put($thumbFilePath, $image->encode()->getEncoded());
		$size = FlySystem::getSize($filePath);
		$data = [];
		$data['name'] = $name.'.pdf';
		$data['path'] = $baseName.'/'.$physicalName;
		$data['mime_type'] = $mimeType;
		$data['thumb'] = $baseName.'/'.$thumbName;
		$data['size'] = $size;

        if(!empty($file) && isset($file['path'])){
			$oldFilePath = config('jp.BASE_PATH').$file['path'];
			FlySystem::delete($oldFilePath);
		}

        if(!empty($file) && isset($file['thumb'])){
			$oldThumbPath = config('jp.BASE_PATH').$file['thumb'];
			FlySystem::delete($oldThumbPath);
		}

        return $data;
	}

    public function getSettingStatus($estimate)
	{
		$manufacturerId = $estimate->manufacturer_id;
		$companyId = $estimate->company_id;
		$data = [];

        if(($type = $estimate->type) && ine($type, 'layer_id')) {
			$layerData =  EstimateTypeLayer::where('company_id', $companyId)
				->where('layer_id', $type['layer_id'])
				->first(['cost', 'cost_type']);

            if($type['amount'] != $layerData->cost) {
				$data['type']['amount'] = $layerData->cost;
			}

            if($type['amount_type'] != $layerData->cost_type) {
				$data['type']['amount_type'] = $layerData->cost_type;
			}
		}

        if(($waterproofing = $estimate->waterproofing) && ine($waterproofing, 'id')) {
			$waterproofingData =  waterproofing::where('company_id', $companyId)
				->where('type_id', $waterproofing['id'])
				->first(['cost', 'cost_type']);

            if($waterproofing['amount'] != $waterproofingData->cost) {
				$data['waterproofing']['amount'] = $waterproofingData->cost;
			}

            if($waterproofing['amount_type'] != $waterproofingData->cost_type) {
				$data['waterproofing']['amount_type'] = $waterproofingData->cost_type;
			}
		}

        if(($level = $estimate->level) && ine($level, 'id')) {
			$levelData =  EstimateLevel::where('company_id', $companyId)
				->where('type_id', $level['id'])
				->first(['fixed_amount']);

            if($level['amount'] != $levelData->fixed_amount) {
				$data['level']['amount'] = $levelData->fixed_amount;
			}
		}

        if(($pitch = $estimate->pitch) && ine($pitch, 'id')) {
			$pitchData =  EstimatePitch::find($pitch['id']);

            if(!$pitchData){
				$data['pitch']['message'] = 'Estimate Pitch Deleted.';
			}elseif($pitch['fixed_amount'] != $pitchData->fixed_amount) {
				$data['pitch']['amount'] = $pitchData->fixed_amount;
			}
		}

        if(($accessToHome = $estimate->access_to_home) && ine($accessToHome, 'amount')) {
			$accessToHomeData =  AccessToHome::where('company_id', $companyId)
				->where('type', $accessToHome['type'])
				->first(['amount']);

            if($accessToHome['amount'] != $accessToHomeData->amount) {
				$data['access_to_home']['amount'] = $accessToHomeData->amount;
			}
		}

        if(($structure = $estimate->structure) && ine($structure, 'type_id')) {
			$structureData =  EstimateStructure::where('company_id', $companyId)
				->where('type_id', $structure['type_id'])
				->first(['amount', 'amount_type']);

            if($structure['amount'] != $structureData->amount) {
				$data['structure']['amount'] = $structureData->amount;
			}

            if($structure['amount_type'] != $structureData->amount_type) {
				$data['structure']['amount_type'] = $structureData->amount_type;
			}
		}

        if(($complexity = $estimate->complexity) && ine($complexity, 'type_id')) {
			$complexityData =  EstimateStructure::where('company_id', $companyId)
				->where('type_id', $complexity['type_id'])
				->first(['amount', 'amount_type']);

            if($complexity['amount'] != $complexityData->amount) {
				$data['complexity']['amount'] = $complexityData->amount;
			}

            if($complexity['amount_type'] != $complexityData->amount_type) {
				$data['complexity']['amount_type'] = $complexityData->amount_type;
			}
		}

        if(($chimnies = $estimate->chimney) && !empty($chimnies)) {
			foreach ($chimnies as $chimney) {
				$chimneyData =  EstimateChimney::find($chimney['id']);

                if(!$chimneyData){
					$data['chimney'][$chimney['size']]['message'] = 'Estimate Chimney Deleted.';
					continue;
				}

                if($chimney['total_amount'] != $chimneyData->amount) {
					$data['chimney'][$chimney['size']]['amount'] = $chimneyData->amount;
				}

                if($chimney['arithmetic_operation'] != $chimneyData->arithmetic_operation) {
					$data['chimney'][$chimney['size']]['arithmetic_operation'] = $chimneyData->arithmetic_operation;
				}
			}
		}

        if(($others = $estimate->others) && !empty($others)) {
			foreach ($others as $other) {
				$otherData =  EstimateVentilation::where('company_id', $companyId)
				->where('type_id', $other['id'])
				->first(['fixed_amount', 'arithmetic_operation']);

                if($other['amount'] != $otherData->fixed_amount) {
					$data['others'][$other['type']]['amount'] = $otherData->fixed_amount;
				}

                if($other['arithmetic_operation'] != $otherData->arithmetic_operation) {
					$data['others'][$other['type']]['arithmetic_operation'] = $otherData->arithmetic_operation;
				}
			}
		}

        if(($gutter = $estimate->gutter) && ine($gutter, 'id')) {
			$gutterData =  EstimateGutter::find($gutter['id']);

            if($gutterData && $gutter['total_size'] && ($gutter['amount'] != $gutterData->amount)) {
				$data['gutter']['amount'] = $gutterData->amount;
			}

            if($gutterData && $gutter['protection_size'] && ($gutter['protection_amount'] != $gutterData->protection_amount)) {
				$data['gutter']['protection_amount'] = $gutterData->protection_amount;
			}
		}

        if(($shingle = $estimate->shingle) && ine($shingle, 'id')) {
			$product = FinancialProduct::find($shingle['id']);

            if(!$product){
				$data['shingle']['message'] = 'Estimate Shingle Deleted';
			}else{
				$levels = $product->levels()->where('manufacturer_id', $manufacturerId)->pluck('level_id')->toArray();

                if(ine($estimate->level, 'id') && !in_array($estimate->level['id'], $levels)){
					$data['shingle']['levels'] = $levels;
				}
			}
		}

        if(($underlayment = $estimate->underlayment) && ine($underlayment, 'id')) {
			$product = FinancialProduct::find($underlayment['id']);

            if(!$product){
				$data['underlayment']['message'] = 'Estimate Underlayment Deleted';
			}else{
				$levels = $product->levels()->where('manufacturer_id', $manufacturerId)->pluck('level_id')->toArray();

                if(ine($estimate->level, 'id') && !in_array($estimate->level['id'], $levels)){
					$data['underlayment']['levels'] = $levels;
				}
			}
        }

		return $data;
	}

    private function saveEstimation($estimateId, $name, $jobId, $fileData, $estimateData)
	{
		$estimation  = Estimation::firstOrNew([
			'clickthru_estimate_id' => $estimateId,
			'job_id' => $jobId,
			'company_id' => getScopeId(),
			'worksheet_id' => null,
		]);

        $estimation->clickthru_estimate_id = $estimateId;
        $estimation->title              =   $name;
        $estimation->created_by        =   Auth::id();;
        $estimation->is_file           =   true;
        $estimation->file_name         =   ine($fileData, 'name') ? $fileData['name'] : null;
        $estimation->file_path         =   ine($fileData, 'path') ? $fileData['path'] : null;
        $estimation->file_mime_type    =   ine($fileData, 'mime_type') ? $fileData['mime_type'] : null;
        $estimation->file_size         =   ine($fileData, 'size') ? $fileData['size'] : null;
        $estimation->thumb             =   ine($fileData, 'thumb') ? $fileData['thumb'] : null;
        $estimation->save();

		return $estimation;
	}

    private function sendEmail($userEmails, $job, $customer, $attachment)
	{
 		try {
 			$user = Auth::user();
			$subject = 'ClickThru Estimate Review Request-'.$customer->full_name.' / '.$job->number;
			$meta = [
				'template'  => 'emails.users.clickthru-estimate',
				'job_id'=> $job->id,
				'customer_id'=> $customer->id
			];

            $this->emailService->sendEmail($subject, null, $userEmails, [], [], $attachment, $user->id, $meta);
		} catch(\Exception $e) {
			Log::info('ClickThru Estimate Send Email: '. getErrorDetail($e));
		}
	}
}