<?php

namespace App\Console\Commands;

use App\Models\Address;
use App\Models\Company;
use App\Models\Subscription;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateBlankLatLongOfAddress extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_blank_lat_long_of_address';
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
		$companies = Company::activated(Subscription::ACTIVE)->pluck('id')->toArray();
 		Address::where(function($query) {
				$query->whereIn('addresses.id', function($query) {
					$query->select('address_id')
					->from('customers')->whereNull('deleted_at');
				})->orWhereIn('addresses.id', function($query) {
					$query->select('address_id')
					->from('jobs')->whereNull('deleted_at');
				})->orWhereIn('addresses.id', function($query) {
					$query->select('billing_address_id')
					->from('customers')->whereNull('deleted_at');
				});
			})
			->with('state', 'country')
			->where('lat', 0)
			->where(function($query){
				$query->whereIn('company_id', function($query){
					$query->select('company_id')->from('subscriptions');
	                $query->where('status', Subscription::ACTIVE);
				});
			})->chunk(100, function($addresses){
		    	foreach ($addresses as $address) {
 		    		log::info('------------------------------------');
		    		log::info('Address Id:'. $address->id);
		    		$addressLine = $address->present()->fullAddressOneLine(true);
		    		if(!$addressLine) {
		    			$address->lat  = null;
						$address->long = null;
						$address->save();
						continue;
		    		}
 		    		$geoLoc = false;// geocode($addressLine);
		    		if(!$geoLoc) {
		    			log::info('Full Address: '.$addressLine);
		    			log::info($geoLoc);
		    			continue;
		    		}
 					$address->lat = $geoLoc['lat'];
					$address->long = $geoLoc['lng'];
					$address->save();
		    	}
		    });
	}
}