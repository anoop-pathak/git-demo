<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class ImportProductPriceCSV extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:import_product_pricing_csv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Improt Product pricing csv for DNA ROOFING AND SIDING';

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
        $this->companyId = 243; // Chatman Security
        $this->company = Company::find($this->companyId);

        if (!$this->company) {
            throw new \Exception("Invalid Company ID");
        }

        // set activity category id
        $activity = FinancialCategory::whereCompanyId($this->companyId)->whereName('ACTIVITY')->first();
        if (!$activity) {
            throw new \Exception("ACTIVITY Category Not Found");
        }
        $this->activityId = $activity->id;

        $this->info('Company Name: ' . $this->company->name);
        $this->info('Import Category: ' . $activity->name);
        if ($this->confirm('Do you wish to continue? [yes|no]')) {
            goto START;
        } else {
            exit;
        }
        // // set labor category id
        // $labor = FinancialCategory::whereCompanyId($this->companyId)->whereName('LABOR')->first();
        // if(!$labor)	throw new Exception("Labor Category Not Found");
        // $this->laborId = $labor->id;

        // // set material category id
        // $material = FinancialCategory::whereCompanyId($this->companyId)->whereName('MATERIALS')->first();
        // if(!$labor)	throw new Exception("Material Category Not Found");
        // $this->materialId = $material->id;

        // // set labor parent dir..
        // $this->laborParentDir = Resource::name(Resource::LABOURS)->company($this->companyId)->first();
        // if(!$this->laborParentDir)	throw new Exception("Labor Parent Dir Not Found");

        // // get system user..
        // $this->systemUser = User::whereCompanyId($this->companyId)->whereGroupId(User::GROUP_ANONYMOUS)->first();
        // if(!$this->systemUser)	throw new Exception("System User Not Found");
        // $this->createdBy = $this->systemUser->id;

        // get csv file..
        // $filename = storage_path().'/data/Dataform product Price Sheet3.xlsx';
        START:
        $filename = storage_path() . '/data/Chatman Securty Products.xlsx';
        $excel = App::make('excel');
        $import = $excel->load($filename);
        $records = $import->get();

        foreach ($records as $key => $record) {
            // if(trim($record->description) == 'Labor') {
            // 	$this->saveLabor($record);
            // }else {
            // $this->saveMaterial($record);
            // }
            $this->saveActivityPricing($record);
        }
    }

    // private function saveLabor($record)
    // {
    // 	DB::beginTransaction();
    // 	try {
    // 		// save user with labor role..
    // 		$labor = User::create([
    // 			'company_id' 	  => $this->companyId,
    // 			'first_name' 	  => $record->item_name,
    // 			'last_name'  	  => ".",
    // 			'group_id'	 	  => User::GROUP_LABOR,
    //          'email'           => "",
    // 			'password' 	 	  => "",
    // 			'active' 	 	  => true,
    // 			'admin_privilege' => 0,
    // 			'company_name' 	  => $this->company->name,
    // 		]);

    // 		UserProfile::create([
    // 			'user_id' 		 => $labor->id,
    //          'address'        => $this->company->office_address,
    // 			'address_line_1' => $this->company->office_address_line_1,
    // 			'city' 			 => $this->company->office_city,
    //          'state_id'       => $this->company->office_state,
    //          'zip'            => $this->company->office_zip,
    // 			'country_id' 	 => $this->company->office_country,
    // 		]);

    // 		FinancialProduct::create([
    // 			'name' 		    => $record->item_name,
    // 			'company_id'    => $this->companyId,
    // 			'labor_id'      => $labor->id,
    // 			'category_id'   => $this->laborId,
    // 			'unit'		    => $record->unit ?: "",
    // 			'unit_cost'	    => $record->unit_cost ?: "",
    // 			'code'		    => $record->item_code,
    // 			'selling_price' => $record->unit_selling_price,
    // 			'description' 	=> $record->description,
    // 		]);

    // 		// create resource dir..
    // 		$dir = new Resource(
    //                [
    //                    'name' => $record->item_name.'_'.$labor->id,
    //                    'company_id' => $labor->company_id,
    //                    'size' => 0,
    //                    'thumb_exists' => false,
    //                    'path' => $this->laborParentDir->path.'/'.strtolower($this->laborParentDir->name),
    //                    'is_dir' => true,
    //                    'mime_type' => null,
    //                    'locked' => false,
    //                    'created_by' => $this->createdBy,
    //                ]
    //            );
    //            $dir->parent_id = $this->laborParentDir->id;
    //            $dir->save();

    //            // attach resource id
    //            $labor->resource_id = $dir->id;
    //            $labor->save();

    // 	} catch (\Exception $e) {
    // 		DB::rollback();
    // 		Log::warning('Labor Import Error: '.$record->item_name);
    // 		Log::warning($e);
    // 	}
    // 	DB::commit();
    // }

    // private function saveMaterial($record)
    // {
    // 	try {
    // 		FinancialProduct::create([
    // 			'name' 		    => $record->item_name,
    // 			'company_id'    => $this->companyId,
    // 			'category_id'   => $this->materialId,
    // 			'unit'		    => $record->unit ?: "",
    // 			'unit_cost'	    => $record->unit_cost ?: "",
    // 			'code'		    => $record->item_code,
    // 			'selling_price' => $record->unit_selling_price,
    // 			'description' 	=> $record->description,
    //      ]);
    // 	} catch (\Exception $e) {
    // 		Log::warning('Material Import Error: '.$record->item_name);
    // 		Log::warning($e);
    // 	}
    // }

    private function saveActivityPricing($record)
    {
        try {
            FinancialProduct::create([
                'name' => $record->item_name,
                'company_id' => $this->companyId,
                'category_id' => $this->activityId,
                'unit' => $record->unit ?: "unit",
                'unit_cost' => $record->unit_cost ?: "",
                'code' => $record->item_code,
                'selling_price' => $record->unit_selling_price,
                'description' => $record->description,
            ]);
        } catch (\Exception $e) {
            $this->error('Activity Import Error: ' . $record->item_name);
            $this->error($e->getMessage());
        }
    }
}
