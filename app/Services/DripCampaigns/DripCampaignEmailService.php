<?php
namespace App\Services\DripCampaigns;

use App\Services\Contexts\Context;
use App\Models\DripCampaignRecipientEmail;
use App\Models\DripCampaignEmailAttachment;
use App\Models\DripCampaignEmail;
use App\Models\Resource;
use App\Services\AttachmentService;

class DripCampaignEmailService {

	public function __construct(DripCampaignEmail $campaignEmailModel,
							Context $scope,
							AttachmentService $attachmentService,
							DripCampaignEmailAttachment $campaignEmailAttachment)
	{
		$this->campaignEmailModel = $campaignEmailModel;
		$this->attachmentService  = $attachmentService;
		$this->scope = $scope;
		$this->campaignEmailAttachment = $campaignEmailAttachment;
	}

	public function createDripCampaignEmail($dripCampaign, $emailCampaign, $emailRecipentsTo, $emailRecipentsCC, $emailRecipentsBCC, $emailAttachments = array())
	{
		$data['drip_campaign_id']  = $dripCampaign->id;
		$data['company_id'] = $this->scope->id();
		$data['subject']    = $emailCampaign['subject'];
		$data['content']    = $emailCampaign['content'];
		$data['email_template_id'] = $emailCampaign['email_template_id'];

		$email = $this->campaignEmailModel->create($data);
		$this->saveRecipients($email, $emailRecipentsTo, $emailRecipentsCC, $emailRecipentsBCC);

		if (!empty($emailAttachments)) {
			$attachments = $this->moveAttachments($emailAttachments);
			$this->saveAttachments($email, $attachments, $emailAttachments);
		}

		return $email;
	}

	private function saveRecipients($dripCampaignEmail, $emailRecipentsTo, $emailRecipentsCC = array(), $emailRecipentsBCC = array()) {

		$emailId = $dripCampaignEmail->id;

		// to recipients
		$to = arry_fu($emailRecipentsTo);
		foreach ($to as $email) {
			DripCampaignRecipientEmail::create([
				'email_campaign_id'	=> $emailId,
				'email'	  	=>	$email,
				'type'	  	=>	'to',
				'company_id' => $this->scope->id()
			]);
		}

		// cc recipients
		$cc = arry_fu($emailRecipentsCC);
		foreach ($cc as $email) {
			DripCampaignRecipientEmail::create([
				'email_campaign_id' => $emailId,
				'email'		=>	$email,
				'type'		=>	'cc',
				'company_id' => $this->scope->id()
			]);
		}

		// bcc recipients
		$bcc = arry_fu($emailRecipentsBCC);
		foreach ($bcc as $email) {
			DripCampaignRecipientEmail::create([
				'email_campaign_id'	=> $emailId,
				'email'		=>	$email,
				'type'		=>	'bcc',
				'company_id' => $this->scope->id()
			]);
		}
	}

	private function saveAttachments($email, array $attachments , $emailAttachments) {
		foreach ($emailAttachments as $key => $emailAttachment) {
			DripCampaignEmailAttachment::create([
				'drip_campaign_email_id' => $email->id,
				'new_resource_id'        => $attachments[$key]['value'],
				'company_id' => $this->scope->id(),
				'ref_type'   => $emailAttachment['ref_type'],
				'ref_id'     => $emailAttachment['ref_id'],
			]);
		}
	}

	private function moveAttachments($emailAttachments)
	{
		$campaignEmailAttachments = [];
		$attacmentEntityType = $this->campaignEmailAttachment->getTable();

		foreach ($emailAttachments as $emailAttachment) {
			$campaignEmailAttachments[] = [
				'type'  => $emailAttachment['ref_type'],
				'value' => $emailAttachment['ref_id']
			];
		}

		$attachments = $this->attachmentService->moveAttachments(Resource::DRIP_CAMPAIGN_EMAIL_ATTACHMENT, $campaignEmailAttachments, $attacmentEntityType);

		return $attachments;
	}
}