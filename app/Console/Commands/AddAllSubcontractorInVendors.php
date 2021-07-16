<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Address;
use App\Models\Vendor;

class AddAllSubcontractorInVendors extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_sub_contractor_vendors';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add all sub contractor in vendors';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	public function fire()
	{
		$totalSubs = User::where('group_id', User::GROUP_SUB_CONTRACTOR)
			->whereNotIn('id', function($query){
				$query->select('ref_id')
					->from('vendors')
					->where('type_id', 2)
					->whereNotNull('ref_id');
			})->count();
		$this->info('Pending Sub - '. $totalSubs);
		User::where('group_id', User::GROUP_SUB_CONTRACTOR)
		    ->with(['company.subscriber' => function($query){
		    	$query->withTrashed();
		    }, 'profile']) ->whereNotIn('id', function($query){
				$query->select('ref_id')
					->from('vendors')
					->where('type_id', 2)
					->whereNotNull('ref_id');
			})->chunk(1000, function($subContractors) use (&$totalSubs) {
				foreach($subContractors as $key => $subContractor){
					$company = $subContractor->company;
					if(!$company) continue;
					$ownerAdmin = $company->subscriber;
					$profile = $subContractor->profile;
					$address = Address::create([
						'company_id'		=> $subContractor->company_id,
						'address'			=> $profile->address,
						'address_line_1'	=> $profile->address_line_1,
						'country_id'		=> $profile->country_id,
						'city'				=> $profile->city,
						'state_id'			=> $profile->state_id,
						'zip'				=> $profile->zip
					]);

					$displayName = implode(' ', [
						$subContractor->first_name,
						$subContractor->last_name,
						$subContractor->company_name
					]);

					$data = [
						'company_id'	=> $subContractor->company_id,
						'first_name'	=> $subContractor->first_name,
						'last_name'		=> $subContractor->last_name,
						'display_name'	=> $displayName,
						'type_id'		=> 2,
						'address_id'	=> $address->id,
						'created_by'	=> $ownerAdmin->id,
						'updated_by'	=> $ownerAdmin->id,
						'ref_id'		=> $subContractor->id,
					];
					Vendor::create($data);
					--$totalSubs;
					$this->info('Pending Sub - '. $totalSubs);
				}
		});

		$totalSubs = User::where('group_id', User::GROUP_SUB_CONTRACTOR)
			->whereNotIn('id', function($query) {
				$query->select('ref_id')
					->from('vendors')
					->where('type_id', 2)
					->whereNotNull('ref_id');
			})->count();

		$this->info('Pending Sub - '. $totalSubs);
	}
}
