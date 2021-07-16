<?php
namespace App\Services\Companies;

use App\Models\User;
use App\Models\UserProfile;
use App\Commands\SubscriberSignupCommand;
class CompaniesService
{
	/**
	 * create a new company for existing user
	 * @param  User 	| $owner 	| Object of a user
	 * @param  Array 	| $input 	| Array of input
	 * @return $company
	 */
	public function create($owner, $input)
	{
		$input['billing_details']['same_as_company_address'] = 1;
		$input['admin_details'] = [
			'first_name'		=> $owner->first_name,
			'last_name'			=> $owner->last_name,
			'email'				=> $owner->email,
			'password'			=> '',
			'address'			=> $owner->profile->address,
            'address_line_1'	=> $owner->profile->address_line_1,
            'city'				=> $owner->profile->city,
            'state_id'			=> $owner->profile->state_id,
            'country_id'		=> $owner->profile->country_id,
            'zip'				=> $owner->profile->zip,
		];
		$input['admin_details']['same_as_company_address'] = 0;

		$command = new SubscriberSignupCommand($input);
		$company = $command->handle();
		$subscriber = $company->subscriber;
		UserProfile::where('user_id', $subscriber->id)->delete();
		User::where('email', $owner->email)
			->where('company_id', $company->id)
			->update(['password' => $owner->password]);

		return $company;
	}
}