<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\CompanyMeta;
use App\Models\Resource;
use App\Services\Resources\ResourceServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class CreateSubscriberResourcesCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_subscriber_resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create subscriber resource in company resources if not exists';

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
        $companies = Company::has('subscriberResource', '=', 0)->get();
        foreach ($companies as $company) {
            $this->createSubscriberResources($company);
        }
    }

    private function createSubscriberResources($company)
    {
        $root = Resource::companyRoot($company->id);
        $resource = App::make(ResourceServices::class)
            ->createDir(Resource::SUBSCRIBER_RESOURCES, $root->id, true);

        CompanyMeta::create([
            'company_id' => $company->id,
            'key' => CompanyMeta::SUBSCRIBER_RESOURCE_ID,
            'value' => $resource->id,
        ]);
        return true;
    }

    // /**
    //  * Get the console command arguments.
    //  *
    //  * @return array
    //  */
    // protected function getArguments()
    // {
    // 	return array(
    // 		array('example', InputArgument::REQUIRED, 'An example argument.'),
    // 	);
    // }

    // /**
    //  * Get the console command options.
    //  *
    //  * @return array
    //  */
    // protected function getOptions()
    // {
    // 	return array(
    // 		array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    // 	);
    // }
}
