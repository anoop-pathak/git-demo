<?php

namespace App\Services\SubContractors;

use App\Exceptions\InvalidResourcePathException;
use App\Models\Job;
use App\Models\JobSchedule;
use App\Models\Resource;
use App\Models\User;
use App\Repositories\ResourcesRepository;
use App\Repositories\SubContractorInvoiceRepository;
use App\Services\Contexts\Context;
use App\Services\Resources\ResourceServices;
use Illuminate\Support\Facades\Auth;
use App\Models\JobMeta;
use App\Repositories\UserRepository;

class SubContractorFilesService
{
    protected $repo;
    protected $scope;
    protected $resourceRepo;
    protected $resourceService;

    /**
     * Class Constructor
     * @param    $repo
     */
    public function __construct(
        SubContractorInvoiceRepository $repo,
        Context $scope,
        ResourcesRepository $resourceRepo,
        ResourceServices $resourceService,
        UserRepository $userRepo
    ) {

        $this->repo = $repo;
        $this->scope = $scope;
        $this->resourceRepo = $resourceRepo;
        $this->resourceService = $resourceService;
        $this->userRepo = $userRepo;
    }

    /**
     * create invoice
     * @param  $job             Job
     * @param  $jobSchedule     JobSchedule
     * @param  $file
     * @param  $input
     * @return invoice
     */
    public function createInvoice($job, $jobSchedule, $file, $input)
    {
        $currentUser = \Auth::user();
        $invoiceDir = $this->getInvoiceDir($job, $currentUser);

        $file = $this->resourceService->uploadFile($invoiceDir->id, $file, false, $job->id);

        return $file;
    }

    /**
     * get invoice list
     * @param  $job
     * @return $invoices
     */
    public function getInvoices($job)
    {
        $currentUser = \Auth::user();
        $invoiceDir = $this->getInvoiceDir($job, $currentUser, false);

        if (!$invoiceDir) {
            return false;
        }

        $invoices = $invoiceDir->descendants()
            ->where('is_dir', '=', false)
            ->whereCompanyId($invoiceDir->company_id)
            ->orderBy('created_at', 'desc');

        return $invoices;
    }

    /**
     * upload file
     * @param  $job
     * @param  $file
     * @return $file
     */
    public function uploadFile($job, $file)
    {
        $currentUser = \Auth::user();
        $subDir = $this->getDir($job, $currentUser);

        $file = $this->resourceService->uploadFile($subDir->id, $file, false, $job->id);

        return $file;
    }

    /**
     * get files
     * @param  $job
     * @return $files object
     */
    public function getFiles($job)
    {
        $currentUser = \Auth::user();
        $subDir = $this->getDir($job, $currentUser, false);

        if (!$subDir) {
            return false;
        }

        $files = $subDir->descendants()
            ->where('is_dir', '=', false)
            ->whereCompanyId($subDir->company_id)
            ->orderBy('created_at', 'desc');

        return $files;
    }

    /**
     * create sub directory
     * @param  $job
     * @param  array $subIds
     * @return $dir
     */
    public function createSubDir($job, $subIds = [])
    {
        $subIds = arry_fu($subIds);
        $dir = [];
        
        if(!$job) {
            return $dir;
        }
        
        if(empty($subIds)) {
            return [];
        }
        
        $filters = [
            'only_sub_contractors' => true,
            'include_sub_contractors' => true,
            'user_ids' => $subIds,
        ];
        
        $users = $this->userRepo->getFilteredUsers($filters)->get();
        
        foreach ($users as $user) {
            $dir[] = $this->getDir($job, $user);
        }

        return $dir;
    }

    public function shareFilesWithSubContrator($job, $subId, $fileIds = [])
    {
        $user = User::findOrFail($subId);
        $copyTo = $this->getDir($job, $user);
        $file = $this->resourceService->copyWithRefrence($copyTo->id, $fileIds);

        return $file;
    }

    /*********************Private Section*************************/


    private function getDir($job, $currentUser, $createIfNotExist = true)
    {
        $jobRootId = $job->getResourceId();

        if (!$this->resourceRepo->isResourceExists($jobRootId)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        $jobSubRootId = $job->getMetaByKey(Resource::SUB_CONTRACTOR_DIR);

        // Check Subcontractor Parent Dir
        if (!$jobSubRootId && $createIfNotExist) {
            $jobSubRootDir = $this->resourceService->createDir(
                Resource::SUB_CONTRACTOR_DIR,
                $jobRootId,
                $locked = true
            );

            $job->saveMeta(Resource::SUB_CONTRACTOR_DIR, $jobSubRootDir->id);
            $jobMeta = JobMeta::where('job_id', $job->id)->get();
            $job->setRelation('jobMeta', $jobMeta);
            $jobSubRootId = $jobSubRootDir->id;
        }

        if (!$jobSubRootId) {
            return false;
        }

        $subDir = Resource::whereParentId($jobSubRootId)
            ->whereCompanyId($this->scope->id())
            ->whereUserId($currentUser->id)
            ->whereIsDir(true)
            ->first();

        if (!$subDir && $createIfNotExist) {
            $dirName = $currentUser->id . '_' . strtolower($currentUser->full_name);
            $subDir = $this->resourceService->createDir(
                $dirName,
                $jobSubRootId,
                false,
                null,
                ['user_id' => $currentUser->id]
            );
        }

        return $subDir;
    }

    private function getInvoiceDir($job, $currentUser, $createIfNotExist = true)
    {
        $subDir = $this->getDir($job, $currentUser);
        $dirName = Resource::SUB_CONTRACTOR_INVOICES;

        $invoiceDir = Resource::whereParentId($subDir->id)
            ->whereCompanyId($this->scope->id())
            // ->whereUserId($currentUser->id)
            ->whereIsDir(true)
            ->whereName($dirName)
            ->first();

        if (!$invoiceDir && $createIfNotExist) {
            $invoiceDir = $this->resourceService->createDir(
                $dirName,
                $subDir->id,
                false,
                null,
                ['user_id' => $currentUser->id]
            );
        }

        return $invoiceDir;
    }
}
