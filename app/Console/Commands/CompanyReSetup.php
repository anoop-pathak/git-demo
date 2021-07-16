<?php
namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyMeta;
use App\Models\Resource;
use App\Services\Contexts\Context;
use App\Services\Resources\ResourceServices;
use App\Services\Setup\CompanySetup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class CompanyReSetup extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:resetup';

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
        $companyId = 395;
        $company = Company::findOrFail($companyId);
        $systemUser = $company->anonymous;
        setScopeId($companyId);

        \Auth::guard('web')->login($systemUser);
        $this->scope = App::make(Context::class);
        $this->resourceService = App::make(ResourceServices::class);
        $this->companySetup = App::make(CompanySetup::class);

        $baseDirectories = \config('resources.BASE_DIRECTORIES');

        $parentDir = Resource::companyRoot($company->id);

        foreach ($baseDirectories as $dirName) {
            $this->resourceService->createDir($dirName, $parentDir->id, true);
        }

        $this->createSubscriberResources($company, $parentDir);
    }

    private function createSubscriberResources($company, $parentDir)
    {
        $resource = $this->resourceService->createDir(Resource::SUBSCRIBER_RESOURCES, $parentDir->id, true);
        CompanyMeta::create([
            'company_id' => $company->id,
            'key' => CompanyMeta::SUBSCRIBER_RESOURCE_ID,
            'value' => $resource->id,
        ]);

        return true;
    }
}
