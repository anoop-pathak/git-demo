<?php
namespace App\Console\Commands;

use Exception;
use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Events\CustomerCreated;
use App\Models\Company;
use App\Models\User;
use App\Models\Job;
use App\Models\Customer;
use App\Models\WorkflowStage;
use App\Models\Flag;
use Illuminate\Support\Facades\App;
use App\Http\Controllers\ProspectsController;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use App\Services\Jobs\JobProjectService;

class CopyCustomersAndJobs extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:copy_customers_and_jobs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = "Copy Customers and Jobs Data from One Subscriber's account to another Subscriber's account";


	protected $copyToCompanyId = null;
	protected $jobNumbers = null;

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
	public function fire()
	{
		$customerIds = [];
		$copyFromCompanyId = $this->ask("Please enter company id from which you want to copy the records: ");
		$fromCompany = Company::findOrFail($copyFromCompanyId);

		$this->copyToCompanyId = $this->ask("\nPlease enter company id in which you want to copy the records: ");

		$company = Company::findOrFail($this->copyToCompanyId);

		$jobNumbers = $this->ask("\nPlease Enter Job Number(s) which you want to copy(eg: '12345-1212-1, 12345-1212-2, 12345-1212-3') Or \n Press Enter to Copy All Records:");

		if(!$jobNumbers){
			$confirmation = $this->ask("\nAre you sure to copy all the Records(Please enter 'y' for yes):");
			if($confirmation != 'y'){
				$this->info('Command Canceled.');
				return false;
			}
		}

		if($jobNumbers){
			$this->jobNumbers = explode(',', $jobNumbers);
			$customerIds = Job::where('company_id', $copyFromCompanyId)
				->whereIn('number', $this->jobNumbers)
				->pluck('customer_id')->toArray();
		}

		$customers = Customer::where('company_id', $copyFromCompanyId)
			->with('jobs', 'phones', 'rep');

		if($jobNumbers && empty($customerIds)){
			$this->info("Please Enter Valid Job Number(s).");
			return false;
		}

		if(!empty($customerIds)){
			$customers = $customers->whereIn('id', $customerIds);
		}

		$customers = $customers->get();
		$totalCustomers = count($customers);

		$prospectusController = App::make(ProspectsController::class);

		$systemUser = $company->anonymous;
		setScopeId($company->id);

		Auth::login($systemUser);

		Config::set('stop_push_notifiction', true);

		$startedAt = $currentDateTIme = Carbon::now()->toDateTimeString();
		$this->info("----- Command started at: $startedAt -----");
		$this->info("----- Total Customers:  $totalCustomers");

		DB::beginTransaction();
		try{
			$customerData = [];
			foreach ($customers as $oldCustomer) {
				$customerData = $this->getData($oldCustomer);
				$newCustomer = $prospectusController->execute("JobProgress\Customers\CustomerCommand", ['input' => $customerData['data']]);
				DB::table('customers_and_jobs_copy_ref')->insert([
					'ref_type' => 'Customer',
					'from_company_id' => $oldCustomer->company_id,
					'from_ref_id' => $oldCustomer->id,
					'to_company_id' => $newCustomer->company_id,
					'to_ref_id' => $newCustomer->id,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString()
				]);
				Event::fire('JobProgress.Customers.Events.CustomerCreated', new CustomerCreated($newCustomer->id));
				$this->info("----- Customer Added -----");
				$this->createJobs($oldCustomer, $newCustomer);
				$this->info("-----Jobs Added -----");
				$totalCustomers--;
				$this->info("Pending Customers: $totalCustomers");
			}

		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}

		DB::commit();

		$completedAt = Carbon::now()->toDateTimeString();

		$this->info("----- Command completed at: $completedAt -----");

	}

	private function getData($customer)
	{
		$customerData = [];
		$phones = $customer->phones->pluck('number', 'label')->toArray();

		$customerData['data'] = $this->mapCustomerData($customer);
		$customerData['data']['address'] = $this->mapAddressInput($customer);
		$customerData['data']['billing'] = $this->mapBillingAddressInput($customer);
		$customerData['data']['phones'] = $this->mapPhonesData($phones);

		return $customerData;
	}

	private function mapCustomerData($customer)
	{
		$data = [
    		'first_name'=> $customer->first_name,
    		'last_name' => $customer->last_name,
    		'company_name' => $customer->company_name,
    		'property_name'=> $customer->property_name,
    		'management_company'=> $customer->management_company,
    		'email'=> $customer->email,
    		'additional_emails' => $customer->additional_emails,
    		'is_commercial' => $customer->is_commercial,
    		'note' => $customer->note,
    		'call_required' => $customer->call_required,
    		'appointment_required' => $customer->appointment_required,
    		'disable_qbo_sync' => $customer->disable_qbo_sync,
    	];

        if($customer->rep_id) {
        	$rep = $customer->rep;
        	if($rep){
        		$repUser = User::where('company_id', $this->copyToCompanyId)
        			->where('first_name', $rep->first_name)
        			->where('last_name', $rep->last_name)
        			->where('email', $rep->email)
        			->first();

    			if($repUser){
    				$data['rep_id'] = $repUser->id;
    			}
        	}
        }

        //Following Code Is working fine But Not used in this release.

        // $data['referred_by_type'] = $customer->referred_by_type;
        // $data['referred_by_note'] = $customer->referred_by_note;

        // if($customer->referred_by_type == 'referral'){
        // 	$ref = $customer->referredByReferral;
        // 	if($ref){
	       //  	$referral = Referral::where('name', $ref->name)
	       //  		->where('company_id', $this->copyToCompanyId)
	       //  		->first();
	       //  	if(!$referral) {
	       //  		$referral = Referral::create([
	       //  			'name' => $ref->name,
	       //  			'company_id' => $this->copyToCompanyId
	       //  		]);
	       //  	}
        // 		$data['referred_by_id'] = $referral->id;
        // 		$data['referred_by_type'] = 'referral';
        // 	}
        // }

        // if($customer->referred_by_type == 'customer'){
        // 	$refCustomer = $customer->referredByCustomer;
        // 	if($refCustomer){
	       //  	$referralCustomer = Customer::where('first_name', $refCustomer->first_name)
	       //  		->where('last_name', $refCustomer->last_name)
	       //  		->where('company_id', $this->copyToCompanyId)
	       //  		->first();
	       //  	if($referralCustomer) {
        // 			$data['referred_by_id'] = $referralCustomer->id;
        // 			$data['referred_by_type'] = 'customer';
	       //  	}
        // 	}
        // }

        // $flags = $customer->flags;
        // $data['flag_ids'] = $this->getFlagIds($flags);
		return $data;
	}

	private function createJobs($oldCustomer, $newCustomer)
	{
		$jobs = $oldCustomer->jobs;
		foreach ($jobs as $job){
			if($this->jobNumbers && !in_array($job->number, $this->jobNumbers)) continue;
			$jobData = [
				'customer_id' => $newCustomer->id,
	    		'name' => $job->name,
	    		'alt_id' => $job->alt_id,
	    		'lead_number' => $job->lead_number,
	    		'description' => $job->description,
	    		'trades'	=> $job->trades->pluck('id')->toArray(),
	    		'duration' => $job->duration,
	    		'same_as_customer_address' => $job->same_as_customer_address,
	    		'appointment_required' => $job->appointment_required,
	    		'call_required' => $job->call_required,
	    		'other_trade_type_description' => $job->other_trade_type_description,
	    		'contact_same_as_customer' => $job->contact_same_as_customer,
	    	];

	    	if(!$job->same_as_customer_address){
	    		$address = $job->address;
	    		if($address){
		    		$jobData['zip'] = $address->zip;
		    		$jobData['city'] =  $address->city;
		    		$jobData['address'] = $address->address;
		    		$jobData['state_id'] = $address->state_id;
		    		$jobData['country_id'] = $address->country_id;
		    		$jobData['address_line_1'] = $address->address_line_1;
	    		}
	    	}

	    	$estimators = $job->estimators;
	    	$jobData['estimator_ids']= $this->getUserIds($estimators);


	    	$reps = $job->reps;
	    	$jobData['rep_ids']= $this->getUserIds($reps);

	    	$subContractors = $job->subContractors->pluck('id')->toArray();
	    	$jobData['sub_contractor_ids']= $this->getUserIds($reps);

			$jobProjectService = App::make(JobProjectService::class);
			$newJob = $jobProjectService->saveJobAndProjects($jobData);

			DB::table('customers_and_jobs_copy_ref')->insert([
				'ref_type' => 'Job',
				'from_company_id' => $job->company_id,
				'from_ref_id' => $job->id,
				'to_company_id' => $newJob->company_id,
				'to_ref_id' => $newJob->id,
				'created_at' => Carbon::now()->toDateTimeString(),
				'updated_at' => Carbon::now()->toDateTimeString()
			]);

			$jobStage = $job->getCurrentStage();
			$stage = WorkflowStage::where('name', $jobStage['name'])
				->where('workflow_id', $newJob->workflow_id)
				->first();


			if($stage && ($stage->code != $newJob->jobWorkflow->current_stage)){
				$jobProjectService->manageWorkFlow($newJob, $stage->code, false);
			}

			$newJob->cs_date = $job->cs_date;
			$newJob->completion_date = $job->completion_date;
			$newJob->material_delivery_date = $job->material_delivery_date;
			$newJob->purchase_order_number = $job->purchase_order_number;
			$newJob->save();
		}
	}

	private function getUserIds($users)
	{
		$ids = [];
		foreach ($users as $user) {
			$assignUser = User::where('company_id', $this->copyToCompanyId)
    			->where('first_name', $user->first_name)
    			->where('last_name', $user->last_name)
    			->where('email', $user->email)
    			->first();
        	if($assignUser){
        		$ids[] = $assignUser->id;
        	}
		}

		return $ids;
	}

	private function getFlagIds($flags)
	{
		$data = [];
		foreach ($flags as $flag) {
        	if($flag->company_id){
        		$flag = Flag::firstOrNew([
        			'company_id' => $this->copyToCompanyId,
        			'title' => $flag->title,
        			'for' => $flag->for,
        		]);
        		$flag->save();
        	}
        	$data[] = $flag->id;
        }

        return $data;

	}

	private function mapAddressInput($customer)
	{
		$data = [];
		$address = $customer->address;
		if($address){
    		$data['zip'] = $address->zip;
    		$data['city'] =  $address->city;
    		$data['address'] = $address->address;
    		$data['state_id'] = $address->state_id;
    		$data['country_id'] = $address->country_id;
    		$data['address_line_1'] = $address->address_line_1;
    		$data['lat'] = $address->lat;
    		$data['long'] = $address->long;
    		$data['same_as_customer_address'] = $customer->same_as_customer_address;
		}
		return $data;
	}

	private function mapBillingAddressInput($customer)
	{
		$data = [];
		$address = $customer->billing;
		if($address){
    		$data['zip'] = $address->zip;
    		$data['city'] =  $address->city;
    		$data['address'] = $address->address;
    		$data['state_id'] = $address->state_id;
    		$data['country_id'] = $address->country_id;
    		$data['address_line_1'] = $address->address_line_1;
    		$data['lat'] = $address->lat;
    		$data['long'] = $address->long;
    		$data['same_as_customer_address'] = $customer->same_as_customer_address;
		}
		return $data;
	}

	private function mapPhonesData($phones)
	{
		$data = [];
		foreach ($phones as $key => $phone) {

			$phoneData = [
				'label' => $key,
				'number' => $phone,
			];
			$data[] = $phoneData;
		}
		return $data;
	}
}
