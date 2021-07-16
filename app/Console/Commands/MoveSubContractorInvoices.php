<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\Resource;
use App\Models\SubContractorInvoice;
use App\Models\User;
use FlySystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class MoveSubContractorInvoices extends Command
{

    protected $resourceService;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:move_sub_contractor_invoices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->resourceService = App::make(\App\Resources\ResourceServices::class);
        $invoices = SubContractorInvoice::get();

        foreach ($invoices as $key => $value) {
            $user = User::whereId($value->user_id)
                ->withTrashed()
                ->first();

            \Auth::guard('web')->login($user);
            setScopeId($user->company_id);

            $job = Job::whereId($value->job_id)
                ->withTrashed()
                ->first();

            $invoiceDir = $this->getDir($value, $user, $job);
            $fileName = basename($value->file_path);

            $invoice = Resource::create([
                'company_id' => $invoiceDir->company_id,
                'parent_id' => $invoiceDir->id,
                'name' => $value->file_name,
                'size' => $value->size,
                'mime_type' => $value->mime_type,
                'created_by' => $value->user_id,
                // 'user_id' 		=> $value->user_id,
                'path' => $invoiceDir->path . '/' . $fileName,
            ]);

            $invoice->created_at = $value->created_at;
            $invoice->updated_at = $value->updated_at;
            $invoice->save();

            $path = config('jp.BASE_PATH') . $value->file_path;
            $newpath = config('resources.BASE_PATH') . $invoice->path;

            // copy file
            FlySystem::copy($path, $newpath);

            // copy thumb
            if ($value->thumb) {
                $thumbPath = config('jp.BASE_PATH') . $value->thumb;
                $thumbFile = basename($thumbPath);
                $thumbName = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $thumbFile);
                $newThumbPath = config('resources.BASE_PATH') . $invoiceDir->path . '/' . $thumbName;
                FlySystem::copy($thumbPath, $newThumbPath);
            }
        }
    }

    /**
     * get invoice directory
     * @param  $value
     * @param  $user
     * @param  $job
     * @return $invoiceDir
     */
    private function getDir($value, $user, $job)
    {
        $jobRootId = $job->getResourceId();
        $jobSubRootId = $job->getMetaByKey(Resource::SUB_CONTRACTOR_DIR);

        if (!$jobSubRootId) {
            $jobSubRootDir = $this->resourceService->createDir(
                Resource::SUB_CONTRACTOR_DIR,
                $jobRootId,
                $locked = true
            );
            $job->saveMeta(Resource::SUB_CONTRACTOR_DIR, $jobSubRootDir->id);
            $jobSubRootId = $jobSubRootDir->id;
        };

        $subDir = Resource::whereParentId($jobSubRootId)
            ->whereCompanyId($value->company_id)
            ->whereUserId($user->id)
            ->whereIsDir(true)
            ->first();

        if (!$subDir) {
            $dirName = $user->id . '_' . strtolower($user->full_name);
            $subDir = $this->resourceService->createDir(
                $dirName,
                $jobSubRootId,
                false,
                null,
                ['user_id' => $user->id]
            );
        }

        $invoiceDirName = Resource::SUB_CONTRACTOR_INVOICES;

        $invoiceDir = Resource::whereParentId($subDir->id)
            ->whereCompanyId($value->company_id)
            ->whereIsDir(true)
            ->whereName($invoiceDirName)
            ->first();

        if (!$invoiceDir) {
            $invoiceDir = $this->resourceService->createDir(
                $invoiceDirName,
                $subDir->id,
                false,
                null,
                []
            );
        }

        return $invoiceDir;
    }
}
