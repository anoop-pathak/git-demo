<?php
namespace App\Services\QuickBooks\Entity;

use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Entity\BaseEntity;
use App\Services\QuickBooks\SynchEntityInterface;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use QuickBooksOnline\API\Data\IPPAttachable;
use QuickBooksOnline\API\Data\IPPReferenceType;
use QuickBooksOnline\API\Data\IPPAttachableRef;
use App\Models\Attachable as AttachableModel;
use App\Repositories\ResourcesRepository;
use App\Services\AttachmentService;
use FlySystem;
use App\Models\VendorBill;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use GuzzleHttp\Client;
use App\Models\VendorBillAttachment;
use App\Models\Resource;

/**
 * @todo division and address mappign
 */
class Attachable extends BaseEntity
{
    private $resourcesRepository;
    private $attachmentService;

	public function __construct(ResourcesRepository $resourcesRepository, AttachmentService $attachmentService)
	{

        parent::__construct();
        $this->resourcesRepository = $resourcesRepository;
        $this->attachmentService = $attachmentService;
        $this->request = new Client;
	}
	/**
	 * Implement base class abstract function
	 *
	 * @return void
	 */
    public function getEntityName()
    {
        return 'attachable';
	}

	public function getJpEntity($qb_id) {

		return AttachableModel::where('quickbook_id', $qb_id)->first();
    }

    /**
	 * Create Bill in QBO
	 *
	 * @param SynchEntityInterface $account
	 * @return SynchEntityInterface
	 */
    public function actionCreate(SynchEntityInterface $attachable)
    {
		try {

            $IPPAttachable = new IPPAttachable();

            $meta = $this->map($IPPAttachable, $attachable);

            $response = $this->dataService->Upload($meta['contents'],
                $meta['name'],
                $meta['mime_type'],
                $meta['object']
            );

            if(!$response->Fault && $response->Attachable) {

                $this->linkEntity($attachable, $response->Attachable);

                return $response->Attachable;
            }

            if($response->Fault) {

                Log::info([$response->Fault]);

                throw new Exception('Unable to upload file.');
            }

	  	} catch (Exception $e) {
            throw $e;
		}
    }

    /**
     * update Bill in QBO
     *
     * @param SynchEntityInterface $vendor
     * @return void
     */
    public function actionUpdate(SynchEntityInterface $bill){

        return null;
    }

    /**
     * delete Bill in QBO
     *
     * @param SynchEntityInterface $vendor
     * @return void
     */

    public function actionDelete(SynchEntityInterface $attachable)
    {
        $IPPAttachable = $this->get($attachable->getQBOId());
        $this->removeAttachableRef($IPPAttachable, $attachable);
        $this->update($IPPAttachable);
        $attachable->delete();

        return $attachable;
    }

    public function actionImport($attachable)
    {
        $data = [];
        $response =  $this->query("SELECT * FROM Attachable WHERE Id ='{$attachable->quickbook_id}'");

        if(empty($response)){
            return $attachable;
        }

        $meta = [
            'job_id' => $attachable->job_id,
            'parent_dir' => Resource::VENDOR_BILL_ATTACHMENTS
        ];

        $qbAttachable = $response[0];

        $resource = $this->saveQBOAttachment($qbAttachable, $meta);

        $this->linkObjectAttachment($attachable, $resource);

        return $attachable;
    }

    public function deleteAttachable($attachable)
    {
        $attachmentId = $attachable->jp_attachment_id;
        VendorBillAttachment::where('value', $attachmentId)
            ->where('vendor_bill_id', $attachable->jp_object_id)
            ->delete();

        if($this->resourcesRepository->isResourceExists($attachmentId)){
            $this->resourcesRepository->removeFile($attachmentId, null);
        }

        $attachable->delete();
        return $attachable;

    }

    public function getAttachable($id, $objectId, $objectType)
    {
        $attachable = AttachableModel::where('quickbook_id', $id)
            ->where('company_id', '=', getScopeId())
            ->where('jp_object_id', $objectId)
            ->where('object_type', $objectType)
            ->first();

        return $attachable;
    }

    public function createTask($objectId, $action, $createdSource, $origin){
        $task = QBOQueue::addTask(QuickBookTask::ATTACHABLE . ' ' . $action, [
                'id' => $objectId,
            ], [
                'object_id' => $objectId,
                'object' => QuickBookTask::ATTACHABLE,
                'action' => $action,
                'origin' => $origin,
                'created_source' => $createdSource
            ]);

        return $task;
    }

    public function getQBAttachables($objectId, $objectType)
    {
        $response = null;
        try {
           $response = $this->query("SELECT * FROM Attachable WHERE AttachableRef.EntityRef.Type ='{$objectType}' AND  AttachableRef.EntityRef.value = '{$objectId}'");
        } catch (Exception $e) {
            Log::info('Get Attachments Exception.');
            Log::info($e);
        }
        return $response;

    }

    /**
     *  Map Synchalbe JP entity to IPP object  of QBO
     *
     * @param IPPIntuitEntity $IPPVendor
     * @param SynchEntityInterface $vendor
     * @return void
     */

	private function map(IPPAttachable $IPPAttachable, SynchEntityInterface $attachable)
    {
        $bill = VendorBill::find($attachable->jp_object_id);

        $file = $this->resourcesRepository->getFile($attachable->jp_attachment_id);

        $fullPath = config('resources.BASE_PATH') . $file->path;

        $contents = FlySystem::read($fullPath);

        $entityRef = new IPPReferenceType([
            'value' => $bill->quickbook_id, 'type' => 'Bill'
        ]);

        $attachableRef = new IPPAttachableRef([
            'EntityRef' => $entityRef
        ]);

        $IPPAttachable->FileName = $file->name;

        $IPPAttachable->AttachableRef = $attachableRef;

        return [
            'contents' => $contents,
            'object' => $IPPAttachable,
            'mime_type' => $file->mime_type,
            'name' => $file->name
        ];
    }

    private function removeAttachableRef(IPPAttachable $IPPAttachable, SynchEntityInterface $attachable)
    {

        $bill = VendorBill::find($attachable->jp_object_id);
        if(!$IPPAttachable->AttachableRef){
            return false;
        }
        $IPPAttachableRefs = (is_array($IPPAttachable->AttachableRef)) ? $IPPAttachable->AttachableRef : [$IPPAttachable->AttachableRef];
        foreach ($IPPAttachableRefs as $IPPAttachableRef) {
            if($IPPAttachableRef->EntityRef == $bill->getQBOId()){
                $IPPAttachableRef->EntityRef = '';
            }
        }
    }


    private function saveQBOAttachment(IPPAttachable $attachable, $meta)
    {
        $fileUri = $attachable->TempDownloadUri;
        $jobId = $meta['job_id'];
        $parentDir = $this->attachmentService->getRootDir($meta['parent_dir']);
        $response = $this->request->get($fileUri);
        $content = $response->getBody()->getContents();

        $fullPath = config('resources.BASE_PATH').$parentDir->path;
        $originalName = $attachable->FileName;
        $physicalName = generateUniqueToken().'_'.$originalName;
        $size = $attachable->Size;
        $mimeType = $attachable->ContentType;

        FlySystem::put($fullPath.'/'.$physicalName, $content, ['ContentType' => $mimeType]);
        $resource = $this->resourcesRepository->createFile($originalName,$parentDir, $mimeType, $size, $physicalName, $jobId, $meta);
        return $resource;
    }

    private function linkObjectAttachment($attachable, $resource)
    {
        $attachable->jp_attachment_id = $resource->id;
        $attachable->save();

        VendorBillAttachment::create([
            'vendor_bill_id' => $attachable->jp_object_id,
            'type'     =>    QuickBookTask::BILL,
            'value'    =>   $resource->id,
        ]);
    }
}