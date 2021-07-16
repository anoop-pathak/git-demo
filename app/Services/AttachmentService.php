<?php
namespace App\Services;

use App\Repositories\EstimateLevelsRepository;
use App\Repositories\ResourcesRepository;
use App\Repositories\ProposalsRepository;
use App\Repositories\EstimationsRepository;
use App\Repositories\WorkOrderRepository;
use App\Services\Resources\ResourceServices;
use App\Repositories\MaterialListRepository;
use App\Exceptions\InvalideAttachment;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\MeasurementRepository;
use App\Models\Resource;
use App\Models\Worksheet;
use App\Models\JobCredit;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use File;
use Illuminate\Support\Facades\App;
use App\Models\JobInvoice;
use FlySystem;
use Carbon\Carbon;
use App\Models\EntityAttachment;
use Exception;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class AttachmentService {

	function __construct(EstimateLevelsRepository $repo,
		ResourcesRepository $resourcesRepo,
		ProposalsRepository $proposalsRepo,
		EstimationsRepository $estimateRepo,
		WorkOrderRepository $workOrderRepo,
		ResourceServices $resourceService,
		MeasurementRepository $measurementRepo,
		MaterialListRepository $materialListRepo
	)
	{
		$this->repo = $repo;
		$this->resourcesRepo = $resourcesRepo;
		$this->proposalsRepo = $proposalsRepo;
		$this->workOrderRepo = $workOrderRepo;
		$this->estimateRepo  = $estimateRepo;
		$this->materialListRepo = $materialListRepo;
		$this->resourceService  = $resourceService;
		$this->measurementRepo  = $measurementRepo;
	}

	/**
	 * Get Attachments files array
	 * @param $attachments Array | Array of attachments; type (Resource or Proposal) and values
	 * @return Array
	 * @access private
	 */
	public function getAttachementFiles(array $attachments = array(), $attacmentEntityType = null)
	{
		$files = [];
		if(empty($attachments)) return $files;
		foreach ($attachments as $key => $attachment) {
			if(!ine($attachment,'type') || !ine($attachment,'value')) {
				throw new InvalideAttachment("Invalid Attachment.");
			}

			$files[] = $this->getFile($attachment['type'], $attachment['value'], $attacmentEntityType);
		}
		return $files;
	}

	public function moveAttachments($rootDir, $attachments, $attacmentEntityType = null)
	{
		if(empty($attachments)) return $attachments;

		$rootDir = $this->getRootDir($rootDir);
		$destination = config('resources.BASE_PATH').$rootDir->path;
		$this->getAttachementFiles($attachments, $attacmentEntityType);
		$resourcesRepo = App::make(ResourcesRepository::class);
		foreach ($attachments as $key => $attachment) {
			$type = $attachment['type'];
			$id   = $attachment['value'];

			switch($type) {

				case 'resource':
				case 'upload':
				$file = $this->resourcesRepo->getFile($id);
				$filePath = config('resources.BASE_PATH').$file->path;
				$name = $file->name;
				$mimeType = $file->mime_type;
				break;

				case 'proposal':
				$file = $this->proposalsRepo->getById($id);
				$filePath = $file->getFilePathWithoutUrl();
				$name = $file->title;
				$mimeType = $file->file_mime_type;
				break;

				case 'estimate':
				$file = $this->estimateRepo->getById($id);
				$filePath = config('jp.BASE_PATH').$file->file_path;
				$name = $file->title;
				$mimeType = $file->file_mime_type;
				break;

				case 'material_list':
				$file = $this->materialListRepo->getById($id);
				$filePath = config('jp.BASE_PATH').$file->file_path;
				$name = $file->title;
				$mimeType = $file->file_mime_type;
				break;

				case 'workorder':
				$file = $this->workOrderRepo->getById($id);
				$filePath = config('jp.BASE_PATH').$file->file_path;
				$name = $file->title;
				$mimeType = $file->file_mime_type;
				break;

				case 'invoice':
				$invoice = JobInvoice::findOrFail($id);
				$token = QuickBooks::getToken();
				$filePath = config('jp.BASE_PATH').$invoice->file_path;
				if($token && $invoice->quickbook_invoice_id) {
					QBInvoice::createOrUpdateQbInvoicePdf($invoice);
					if($invoice->qb_file_path){
						$filePath = config('jp.BASE_PATH').$invoice->qb_file_path;
					}
				}
				$name 	  =  $invoice->title;
				$mimeType = 'application/pdf';
				break;

				case 'worksheet':
				$worksheet = Worksheet::findOrFail($id);
				$filePath  = config('jp.BASE_PATH').$worksheet->file_path;
				$name      = $worksheet->type;
				$mimeType  = 'application/pdf';
				break;

				case 'credit':
				$jobCredit = JobCredit::findOrFail($id);
				$filePath  = config('jp.BASE_PATH').$jobCredit->file_path;
				$name = 'credit_'.basename($jobCredit->file_path);
				$mimeType = 'application/pdf';
				break;

				case 'measurement':
				$measurement = $this->measurementRepo->getById($id);
				$mimeType = 'application/pdf';
				$name 	  =  $measurement->title;
				$filePath = config('jp.BASE_PATH').$measurement->file_path;
				break;

				default:
				throw new InvalideAttachment("Invalid Attachment.");
			}

			// get file extension..
			$extension = File::extension($filePath);

			// get file size
			$size = FlySystem::getSize($filePath);

			// create physical file name..
			$physicalName = Carbon::now()->timestamp.'_'.rand().'.'.$extension;

			// copy file to attachment directory..
			$resource = $this->resourceService->copy($rootDir, $filePath, $destination, $name, $mimeType, $size, $physicalName, null, $meta = []);

			$attachments[$key]['value']	= $resource->id;
		}

		return $attachments;
	}

	/**
	 * Get File path for attchment
	 * @param $type String | type of attachment file (e.g., resource or proposal)
	 * @param $id Int or String | id of resource or proposal
	 * @return String (path of file)
	 * @access private
	 */
	private function getFile($type, $id, $attacmentEntityType = null) {
		$fileData = [];
		try {

			switch($type) {

				case 'resource':
				case 'upload':
				$resource = $this->resourcesRepo->getFile($id);
				$fileData['name'] = basename($resource->path);
				$fileData['path'] = config('resources.BASE_PATH').$resource->path;
				break;

				case 'proposal':
				$proposal = $this->proposalsRepo->getById($id);
				$fileData['name'] = 'proposal_'.basename($proposal->file_path);
				$fileData['path'] = $proposal->getFilePathWithoutUrl();
				break;
				case 'estimate':
				$estimate = $this->estimateRepo->getById($id);
				$fileData['name'] = 'estimate_'.basename($estimate->file_path);
				$fileData['path'] = config('jp.BASE_PATH').$estimate->file_path;
				break;
				case 'material_list':
				$materialList = $this->materialListRepo->getById($id);
				$fileData['name'] = 'material_list_'.basename($materialList->file_path);
				$fileData['path'] = config('jp.BASE_PATH').$materialList->file_path;
				break;
				case 'workorder':
				$workOrder = $this->workOrderRepo->getById($id);
				$fileData['name'] = 'workorder_'.basename($workOrder->file_path);
				$fileData['path'] = config('jp.BASE_PATH').$workOrder->file_path;
				break;
				case 'invoice':
				$invoice = JobInvoice::findOrFail($id);
				$token = QuickBooks::getToken();
				$filePath = config('jp.BASE_PATH').$invoice->file_path;

				if(!$invoice->file_size) {
					$filePath = 'public/'.$invoice->file_path;
				}
				$fileData['name'] = 'invoice_'.basename($invoice->file_path);
				$fileData['path'] =  $filePath;

				if($token && $invoice->quickbook_invoice_id) {
					QBInvoice::createOrUpdateQbInvoicePdf($invoice);
					if($invoice->qb_file_path){
						$filePath = config('jp.BASE_PATH').$invoice->qb_file_path;
						$fileData['name'] = 'invoice_'.basename($invoice->qb_file_path);
						$fileData['path'] =  $filePath;
					}
				}
				break;
				case 'worksheet':
				$worksheet = Worksheet::findOrFail($id);
				$filePath = config('jp.BASE_PATH').$worksheet->file_path;
				$fileData['path'] = $filePath;
				$fileData['name'] = $worksheet->type;
				break;
				case 'credit':
				$jobCredit = JobCredit::findOrFail($id);
				$filePath  = config('jp.BASE_PATH').$jobCredit->file_path;
				$fileData['path'] = $filePath;
				$fileData['name'] = 'credit_'.basename($jobCredit->file_path);
				break;
				case 'measurement':
				$measurement = $this->measurementRepo->getById($id);
				$fileData['name'] = 'measurement_'.basename($measurement->file_path);
				$fileData['path'] = config('jp.BASE_PATH').$measurement->file_path;
				break;
				default:
				goto Invalide;
			}

			if($attacmentEntityType == 'vendor_bills') {
				// get file extension..
				$allowedfileExtension = ['png','doc','xlsx','csv','jpeg','gif','pdf','tiff','xml'];
				$extension = File::extension($fileData['path']);
				$check = in_array($extension,$allowedfileExtension);
				if (!$check) {
					throw new InvalideAttachment("The attachments must be a file of type: xlsx, csv, jpeg, png, doc, pdf, tiff, xml, gif.");
				}

				// get file size
				$size = FlySystem::getSize($fileData['path']);
				if ($size > 20971520) {
					throw new InvalideAttachment("Your attachment is bigger than the 20 MB file size limit. Try a smaller file size.");
				}
			}

			return $fileData;

		}catch(Exception $e) {
			Invalide : throw new InvalideAttachment("Invalid Attachment.");
		}
	}

	public function getRootDir($rootDir) {
		$scope = App::make(Context::class);
		$parentDir = Resource::name($rootDir)->company($scope->id())->first();
		if(!$parentDir){
			$root = Resource::companyRoot($scope->id());
			$parentDir = $this->resourceService->createDir($rootDir, $root->id);
		}
		return $parentDir;
	}

	public function removeAttachments($schedule, $resouceIds)
	{
		EntityAttachment::whereIn('value', $resouceIds)
			->where('entity_id', $schedule->id)
			->Where('entity_type', 'job_schedules')
			->delete();
	}

	public function saveAttachments($schedule, $attacmentEntityType, $attachments = array())
	{
		// move attachment
		$savedAttachment = $this->moveAttachments(Resource::JOB_SCHEDULES_ATTACHMENTS, $attachments, $attacmentEntityType);

		// save attachment
		$attachments = $this->save($schedule, $attacmentEntityType, $savedAttachment);
	}

	private function save($schedule, $attacmentEntityType, $attachments = array())
	{
		foreach ($attachments as $attachment) {
			$payload = [
				'company_id'  => getScopeId(),
				'entity_id'	  => $schedule->id,
				'entity_type' => $attacmentEntityType,
				'type'        => $attachment['type'],
				'value'	   	  => $attachment['value'],
				'created_by'  => Auth::id()
			];
			EntityAttachment::create($payload);
		}
	}
}