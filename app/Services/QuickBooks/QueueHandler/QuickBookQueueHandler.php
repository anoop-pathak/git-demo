<?php namespace App\Services\QuickBooks\QueueHandler;

use Illuminate\Support\Facades\App;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use Exception;
use App\Repositories\JobRepository;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Auth;

class QuickBookQueueHandler
{
	/**
	 * Sync Job
	 * @param $job     Object (Queue)
	 * @param $jobData Job Data
	 */
	public function syncJob($job, $jobData)
	{
		try {
			if($this->setCompanyScope($jobData)) {
				$jobRepo = App::make(JobRepository::class);
				$data = $jobRepo->getById($jobData['id']);

				$token = QuickBooks::getToken();
				if($token) {
					QBCustomer::getJobQuickbookId($data);
				}
			}

		} catch(UnauthorizedException $e) {
			//error handle
		} catch(QuickBookException $e) {
			//error handle
		} catch (Exception $e) {
			//error Handle
		}

		$job->delete();
	}

	/**
	 * Sync Customer
	 * @param $job      Object (Queue)
	 * @param $customer Customer Data
	 */
	public function syncCustomer($job, $customer)
	{
		try {
			if($this->setCompanyScope($customer)) {
				// $customerRepo = App::make('JobProgress\Repositories\CustomerRepository');

				// $customer = $customerRepo->getById($customer['id']);

				QBCustomer::update($customer['id'], $in = 'QB');

				// $qbService = App::make('JobProgress\QuickBooks\QuickBookService');
				// $token = $qbService->getToken();

				// if($token) {
				// 	$qbService->createOrUpdateCustomer($token, $customer);
				// }
			}

		} catch(UnauthorizedException $e) {
			//error handle
		} catch(QuickBookException $e) {
			//error handle
		} catch (Exception $e) {
			//error Handle
		}

		$job->delete();
	}

	/**
	 * Set Company Scope
	 */
	private function setCompanyScope($data)
	{
		$user = User::find(Crypt::decrypt($data['current_user_id']));
		if(!$user) return false;

		Auth::login($user);

		setScopeId($user->company_id);

		return true;
	}
}