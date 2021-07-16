<?php

namespace App\Handlers\Events;

use App\Models\Address;
use Solr;
use Firebase;
use Log;
use Exception;

class CustomerQueueHandler
{
	/**
	* add or update customer on solr
	*/
	public function customerIndexSolr($jobQueue, $data = [])
	{
		Solr::customerIndex($data['customer_id']);
		$jobQueue->delete();
	}
 	/**
	* delete customer from solr
	*/
	public function customerDeleteSolr($jobQueue, $data = [])
	{
		Solr::customerJobDelete($data['customer_id']);
		$jobQueue->delete();
	}
 	/**
	* update workflow in firebase
	*/
	public function updateWorkflow($jobQueue, $data = [])
	{
		$scope = setAuthAndScope($data['current_user_id']);
		if(!$scope) return $jobQueue->delete();
 		Firebase::updateWorkflow();
		$jobQueue->delete();
	}
 	/**
	* attach geo location
	*/
	public function attachGeoLocation($jobQueue, $data = [])
	{
		try {
			$addressId = $data['address_id'];
 			$address = Address::where('id', $addressId)->first();
			if(!$address) return $jobQueue->delete();
 			$location = null;
 			$fullAddress = $address->present()->fullAddressOneLine;
			if(!empty(trim($fullAddress))) {
				$location = geocode($fullAddress);
			}
			
			if(!$location) {
				$address->geocoding_error = true;
				$address->save();
			} else {
				$address->lat = $location['lat'];
				$address->long = $location['lng'];
				$address->save();
			}
			
			// delete queue
			$jobQueue->delete();
		} catch (Exception $e) {
			\Log::error($e);
		}
	}
} 