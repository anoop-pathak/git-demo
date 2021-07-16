<?php
namespace App\Handlers\Events;

use FlySystem;
use App\Models\Proposal;
use App\Services\DigitalSignature;
use Exception;
use JobQueue;
use Settings;
use Mail;
use App\Models\Resource;

class ProposalDigitalAuthorizationHandler
{
	public function fire($queueJob, $data)
	{
		try {
			JobQueue::markInProcess($data['queue_status_id'], $queueJob->attempts());

			$proposal = Proposal::find($data['proposal_id']);

			if(!$proposal) {
				JobQueue::markFailed($data['queue_status_id'], $queueJob->attempts());
				JobQueue::saveErrorMessage($data['queue_status_id'], "Proposal not found");

				return $queueJob->delete();
			}

			setScopeId($proposal->company_id);

			if(!$proposal->digital_signed) {
				$digitalSignService = app(DigitalSignature::class);

				$proposalPath = $proposal->file_path;
				$content = $proposal->getFileContent();

				$fileData = $digitalSignService->authorizeFile($proposalPath, $content);

				// For Subcontractor worksheets
				if($proposal->isWorksheet()) {
					$path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $proposalPath);
					$fullPathSub = config('jp.BASE_PATH').$path;

					if(FlySystem::exists($fullPathSub)) {
						$subFileContent = FlySystem::read($fullPathSub);
						$digitalSignService->authorizeFile($path, $subFileContent);
					}
				}

				$proposal->file_path = $fileData['file_path'];
				$proposal->file_size = $fileData['file_size'];
				$proposal->digital_signed = true;
				$proposal->save();
			}

			JobQueue::markCompleted($data['queue_status_id'], $queueJob->attempts());

			if($proposal->digital_signed && !ine($data, 'stop_notifications')) {
				$this->sendSuccessNotification($proposal);
			}

			return $queueJob->delete();
		} catch (Exception $e) {

			JobQueue::saveErrorMessage($data['queue_status_id'], $e);

			if($queueJob->attempts() >= config('queue.failed_attempts')) {
				JobQueue::markFailed($data['queue_status_id'], $queueJob->attempts());

				// $this->sendErrorNotification($proposal);

				return $queueJob->delete();
			}
		}
	}

	private function sendSuccessNotification($proposal)
	{
		$company = $proposal->company;
		$customer = $proposal->job->customer;
		$content = "This is just to let you know that <b>{$customer->full_name}</b> has signed your document <span style='color: #357ebd;'>{$proposal->file_name}</span>. Here’s your copy, available online.";

		$filePath = $proposal->file_path;

		$pathInfo = explode('/', $filePath);
		$fileName = end($pathInfo);

		$companyRoot = Resource::companyRoot(getScopeId());
		$emailFileBasePath = $companyRoot->path.'/'.config('resources.DIGITAL_AUTHORIZED_DIR').'/email_attachments/';

		$emailFilePath = $emailFileBasePath.$fileName;
		$destination = config('resources.BASE_PATH').$emailFilePath;
		$config['ACL'] = 'public-read';

		if(!FlySystem::exists($destination)) {
			FlySystem::copy($filePath, $destination, $config);
		}

		$proposalFileUrl = FlySystem::getUrl($destination, false);
		$data = [
			'company'  => $company,
			'proposal' => $proposal,
			'customer' => $customer,
			'content' => $content,
			'proposalFileUrl' => $proposalFileUrl,
		];

		$subject = "{$customer->full_name} just signed your proposal.";

		$setting = \Settings::get('CUSTOMER_SYSTEM_EMAILS');
		if(ine($setting, 'send_digital_copy_email') && isTrue($setting['send_digital_copy_email'])) {
			$this->sendMailToCustomer($company, $proposal, $proposalFileUrl);
		}

		// sub contractor worksheet
		$subcontractorFileUrl = null;
		$subcontractorFilePath = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $filePath);
		if($proposal->isWorksheet() && FlySystem::exists($subcontractorFilePath)) {

			$subPathInfo = explode('/', $subcontractorFilePath);
			$subFileName = end($subPathInfo);
			$emailSubFilePath = $emailFileBasePath.$subFileName;

			$subFileDestination = config('resources.BASE_PATH').$emailSubFilePath;

			if(!FlySystem::exists($subFileDestination)) {
				FlySystem::copy($subcontractorFilePath, $subFileDestination, $config);
			}

			$subcontractorFileUrl = FlySystem::getUrl($subFileDestination, false);
		}

		$users = $this->getNotifyUsers($proposal);
		foreach ($users as $user) {
			if($user['data_masking']) {
				$data['proposalFileUrl'] = $subcontractorFileUrl ?: '';
			}
			Mail::send('emails.proposal-digitally-authorized', $data, function($message) use ($user, $subject)
			{

				$message->to($user['email'])->subject($subject);
			});
		}
	}

	private function sendErrorNotification($proposal)
	{
		$company = $proposal->company;
		$content = "The signature couldn't be processed due to some issues. Please send the document again.";
		$customer = $proposal->job->customer;

		$data = [
			'company'  => $company,
			'content'  => $content,
			'proposal' => $proposal
		];

		$subject = "Failure to get {$customer->full_name} signature.";

		$users = $this->getNotifyUsers($proposal);
		foreach ($users as $user) {
			Mail::send('emails.proposal-digitally-authorized', $data, function($message) use ($user, $subject)
			{

				$message->to($user['email'])->subject($subject);
			});
		}
	}
	private function sendMailToCustomer($company, $proposal, $proposalFileUrl)
	{
		if(!$job = $proposal->job) return;

		if((!$customer = $job->customer) || !$customer->email) return;

		$subject = "Download the signed Document.";

		$data = compact('company', 'proposal', 'proposalFileUrl');
		$customerEmail = $customer->email;

		Mail::send('emails.proposal-digitally-authorized-customer', $data, function($message) use ($customerEmail, $subject)
		{
			$message->to($customerEmail)->subject($subject);
		});
	}

	private function getNotifyUsers($proposal)
	{
		// notify To
		$notifyTo =  Settings::get('PROPOSAL_ACCEPTANCE_NOTIFICATION');
		$company = $proposal->company;

		$toOwner  = (isset($notifyTo['owner']) && (isTrue($notifyTo['owner'])));
		$toAdmins = (isset($notifyTo['admins']) && (isTrue($notifyTo['admins'])));
		$toCustomerRep = (isset($notifyTo['customer_rep'])
			&& (isTrue($notifyTo['customer_rep'])));
		$toEstimator = (isset($notifyTo['estimators']) && (isTrue($notifyTo['estimators'])));
		$toSender = (isset($notifyTo['sender']) && (isTrue($notifyTo['sender'])));

		$users = [];
		if($toAdmins) {
			$admins = $company->admins()->active()->get();
			if(sizeof($admins)) {
				$users = $admins->toArray();
			}
		}
		if($toOwner) {
			$users[] = $company->subscriber->toArray();
		}
		$job = $proposal->job;
		$customer = $job->customer;

		if($toCustomerRep && ($rep = $customer->rep()->active()->first()) ) {
			$users[] = $rep->toArray();
		}
		if($toEstimator) {
			$estimators = $job->estimators()->active()->get();

			if(sizeof($estimators)) {
				$estimators = $estimators->toArray();
				$users = array_merge($users, $estimators);
			}
		}
		if($toSender) {
			if($sender = $proposal->sharedBy)
				$users[] = $sender->toArray();
		}

		$users = uniqueMultidimArray($users);

		return $users;
	}
}