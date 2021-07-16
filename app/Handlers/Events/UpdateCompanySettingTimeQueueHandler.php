<?php
namespace App\Handlers\Events;

use Firebase;
use Log;
use Exception;

class UpdateCompanySettingTimeQueueHandler
{
	public function updateComapnySettingUpdateTime($jobQueue, $data)
	{
		try {
			if(!ine($data, 'company_id')) {
				$jobQueue->delete();
				return true;
			}

			setScopeId($data['company_id']);

			Firebase::updateCompanySetting($data['user_id']);

		} catch(Exception $e) {
			Log::info('Update Company Setting Time Queue Handler Error: ');
			Log::error($e);
		}

		$jobQueue->delete();
	}
}