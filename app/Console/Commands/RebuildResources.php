<?php

namespace App\Console\Commands;

use App\Models\ApiResponse;
use App\Models\Resource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildResources extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:rebuild-resources';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild Resources';

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
        set_time_limit(0);

        // EnterCompanyID : $companyID = $this->ask('Enter Company Id : ');
        // $company = Company::whereId($companyID)->withTrashed()->first();
        // if(!$company) {
        // 	$this->error('Invalid Company Id');
        // 	goto EnterCompanyID;
        // }
        // DB::table('new_resources')->whereCompanyId($companyID)->delete();

        try {
            DB::table('resources')->whereNull('deleted_at')
                // ->whereCompanyId($companyID)
                ->chunk(200, function ($resources) {
                    // OldResource::chunk(200, function($resources){
                    foreach ($resources as $resource) {
                        $this->copyToNewTable($resource);
                    }
                });
        } catch (\Exception $e) {
            $this->error(ApiResponse::errorInternal('error', $e));
        }
    }

    private function copyToNewTable($oldResource)
    {
        try {
            $resource = new Resource((array)$oldResource);
            $resource->save();
            echo "\n $resource->id";
        } catch (\Exception $e) {
            if ($e->getMessage() != 'Trying to get property of non-object') {
                throw $e;
            }
            echo "\n $resource->id : Parent not found";
        }
    }
}
