<?php
namespace App\Handlers\Events;

use Setting;
use App\Models\User;
use Firebase;

class FirebaseUpdateRestrictedWorkflowHandler
{
	public function fire($queueJob, $data)
	{
		$companyId = $data['company_id'];
		setScopeId($companyId);

		$users = User::whereCompanyId($companyId)
			->whereGroupId(User::GROUP_STANDARD_USER)
			->get();

		foreach ($users as $user) {
			try {
				Firebase::updateUserSettings($user);
			} catch (\Exception $e) {
				\Log::info("FirebaseUpdateRestrictedWorkflowHandler Error: ".(string)$e);
			}
		}

		Firebase::updateWorkflow();

		$queueJob->delete();
	}
}