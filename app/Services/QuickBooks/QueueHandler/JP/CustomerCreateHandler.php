<?php namespace App\Services\QuickBooks\QueueHandler\JP;

use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\CompanyScopeTrait;

class CustomerHandler
{
	use CompanyScopeTrait;

	public function create($job, $payload)
	{
		$this->setCompanyScope(21);

		Log::info('Customer:Create', func_get_args());
	}

	public function update($job, $payload)
	{
		Log::info('Customer:Update', func_get_args());
	}

	public function delete($job, $payload)
	{
		Log::info('Customer:Create', func_get_args());
	}
}