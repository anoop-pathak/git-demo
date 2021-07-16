<?php

namespace App\Services\Emails;

use App\Exceptions\InvalideAttachment;
use App\Models\ActivityLog;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\JobCredit;
use App\Models\JobInvoice;
use App\Models\Proposal;
use App\Models\Resource;
use App\Models\Worksheet;
use App\Repositories\EmailsRepository;
use App\Repositories\EmailTemplateRepository;
use App\Repositories\EstimationsRepository;
use App\Repositories\MaterialListRepository;
use App\Repositories\ProposalsRepository;
use App\Repositories\ResourcesRepository;
use ActivityLogs;
use FlySystem;
use Firebase;
use App\Services\Resources\ResourceServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
// use Queue;
use App\Repositories\MeasurementRepository;
use App\Repositories\WorkOrderRepository;
use App\Services\QuickBooks\QuickBookService;
use Settings;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\DripCampaignRepository;


class EmailServices
{

    /**
     * Email Repo
     * @var \App\Repositories\EmailsRepository
     */
    protected $repo;

    /**
     * Resources Repo
     * @var \App\Repositories\ResourcesRepository
     */
    protected $resourcesRepo;

    /**
     * Proposals Repo
     * @var \App\Repositories\ProposalsRepository
     */
    protected $proposalsRepo;

    /**
     * Estimates Repo
     * @var \App\Repositories\EstimationsRepository
     */
    protected $estimateRepo;

    /**
     * MaterialList Repo
     * @var \App\Repositories\MaterialListRepository
     */
    protected $materialListRepo;

    /**
     * WorkOrder Repo
     * @var \App\Repositories\WorkOrderRepository
     */
    protected $workOrderRepo;

    /**
     * Resources Service
     * @var \App\Resources\ResourceServices
     */
    protected $resourceService;

    /**
	 * DripCampaign Repository
	 * @var \App\Repositories\DripCampaignRepository
	 */
	protected $dripCampaignRepo;

    function __construct(
        EmailsRepository $repo,
        ResourcesRepository $resourcesRepo,
        ProposalsRepository $proposalsRepo,
        EstimationsRepository $estimateRepo,
        EmailTemplateRepository $emailTemplateRepo,
        MaterialListRepository $materialListRepo,
        ResourceServices $resourceService,
        MeasurementRepository $measurementRepo,
        WorkOrderRepository $workOrderRepo,
        QuickBookService $quickBookService,
        DripCampaignRepository $dripCampaignRepo
    ) {

        $this->repo = $repo;
        $this->resourcesRepo = $resourcesRepo;
        $this->proposalsRepo = $proposalsRepo;
        $this->estimateRepo = $estimateRepo;
        $this->emailTemplateRepo = $emailTemplateRepo;
        $this->materialListRepo = $materialListRepo;
        $this->measurementRepo  = $measurementRepo;
        $this->resourceService = $resourceService;
        $this->workOrderRepo = $workOrderRepo;
        $this->quickBookService = $quickBookService;
        $this->dripCampaignRepo = $dripCampaignRepo;
    }

    /**
     * Send Mail
     * @param $subject String | Subject of Mail
     * @param $content Text | Content of Mail
     * @param $from String | From Address
     * @param $to Array | Array of emails to
     * @param $cc Array | Array of emails cc
     * @param $bcc Array | Array of emails bcc
     * @param $attachments Array | Array of attachments
     * @param $replyTo Int | Email Id (If it is a reply of an existing mail)
     * @param $createdBy Integer | Current user id (who sending this email)
     * @param $meta Array | Array of meta data (customer_id, job_id)
     * @return object
     * @access public
     */
    public function sendEmail($subject, $content, $to, $cc = [], $bcc = [], $attachments = [], $createdBy, $meta = [])
    {
        DB::beginTransaction();
        $meta['stop_transaction'] = true;
        $logEnter  = 'default';
        try {
            $currentUser = Auth::user();
            $files = $this->getAttachementFiles($attachments);
            //save mail..
            $attachments = $this->moveAttachments($attachments, $meta);
            $fromAddress = $currentUser->email;
            $email = $this->repo->save(
                $type = Email::SENT,
                $subject,
                $content,
                $fromAddress,
                $to,
                $cc,
                $bcc,
                $attachments,
                $createdBy,
                $meta
            );

            // maintain activity log..
            if (ine($meta, 'stage_code') && ine($meta, 'main_job_id')) {
                $this->maintainActivityLog($meta);
            }

            // manage proposal status..
            if (ine($meta, 'proposal_id')) {
                $proposal = Proposal::find($meta['proposal_id']);
                if (!is_null($proposal) && !in_array($proposal->status, ['sent', 'accepted'])) {
                    $proposal->status = Proposal::SENT;
                    $proposal->shared_by = Auth::id();
                    $proposal->save();
                }
            }

            $company = $currentUser->company;
            $replyToAddress = $this->getReplyToAddress($email);

            if (filter_var(Settings::get('USER_BCC_ADDRESS'), FILTER_VALIDATE_EMAIL)) {
                $bcc[] = Settings::get('USER_BCC_ADDRESS');
                $logEnter = $logEnter.'_BLOCK1'.Settings::get('USER_BCC_ADDRESS');
                $setting = Settings::getSettingDetailByKey('USER_BCC_ADDRESS');
				if($setting['user_id'] != Auth::id()) {
					$data = [
						'Auth Id' => Auth::id(),
						'Authorizer Owner id' =>  \Authorizer::getResourceOwnerId(),
						'Setting Id' => $setting['id'],
						'Setting User Id' => $setting['user_id'],
						'Setting Company Id' => $setting['company_id'],
						'Setting BCC Email' => $setting['value'],
						'Email Id' => $email->id,
					];
					Log::error('Sending Email setting mismatched', $data);
					$bcc = array_diff($bcc, [Settings::get('USER_BCC_ADDRESS')] );
				}
            }

            //add customer rep in bcc
            if (Settings::get('CUSTOMER_REP_IN_BCC')
                && ($email->customer)
                && ($rep = $email->customer->rep)) {
                $bcc[] = $rep->email;
                $logEnter = $logEnter.'_BLOCK2_'.$rep->email;
            }

            $data = [
                'subject' => $subject,
                'content' => $content,
                'user_id' => $currentUser->id,
                'to' => $to,
                'cc' => $cc,
                'bcc' => $bcc,
                'files' => $files,
                'reply_to' => $replyToAddress,
                'email_id' => $email->id,
                'website_link' => Settings::get('WEBSITE_LINK'),
                'template' => ine($meta, 'template') ? $meta['template']: null,
            	'job_id' => ine($meta, 'job_id') ? $meta['job_id']: null,
            	'customer_id' => ine($meta, 'customer_id') ? $meta['customer_id']: null,
            ];
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        Queue::connection('email')->push(\App\Services\Emails\EmailQueueHandler::class, $data);

        $metaLog = $meta;
		unset($metaLog['content']);
		Log::info('sendEmail information '.$email->id, [
			'Context' => getScopeId(),
			'Setting_User' => Settings::getUser(),
			'logged_in_user_id' => $currentUser->id,
			'logged_in_user_comany_id' => $currentUser->company_id,
			'CUSTOMER_REP_IN_BCC' => Settings::get('CUSTOMER_REP_IN_BCC'),
			'USER_BCC_ADDRESS' => Settings::get('USER_BCC_ADDRESS'),
			'DATA'=> [
				'to'	  	=>	$to,
            	'cc'	  	=>	$cc,
            	'bcc'	  	=>	$bcc,
            	'email_id'	=> 	$email->id,
            ],
			'Input' => $metaLog,
			'Flow_Data' => $logEnter,
		]);

        return $email;
    }

    public function createEmailTemplate(
        $title,
        $template,
        $active,
        $createdBy,
        $attachments = [],
        $subject = null,
        $meta = []
    ) {

        $attachments = $this->moveAttachments($attachments);
        $emailTemplate = $this->emailTemplateRepo->saveTemplate(
            $title,
            $template,
            $active,
            $createdBy,
            $attachments,
            $subject,
            $meta
        );
    }

    public function updateEmailTemplate(EmailTemplate $emailTemplate, $title, $template, $active, $attachments = [], $subject = null, $meta)
    {
        $attachments = $this->moveAttachments($attachments);
        $emailTemplate = $this->emailTemplateRepo->updateTemplate(
            $emailTemplate,
            $title,
            $template,
            $active,
            $attachments,
            $subject,
            $meta
        );
    }

    /**
     * *******
     * @param  [type] $type       [type]
     * @param  [type] $value      [id]
     * @param  [type] $templateId [template id]
     * @param  [type] $file       [file object]
     * @return [type]             [resource]
     */
    public function templateAttachFile($type, $value, $templateId, $file)
    {
        $attachment = [
            'type' => $type,
            'value' => $value
        ];
        if (!empty($file) && $type == 'file') {
            $attachments = $this->uploadAttachment($type, $file);
        } else {
            $attachments = $this->moveAttachments([$attachment])[0];
        }
        return $this->emailTemplateRepo->addAttachment($templateId, $attachments);
    }

    /**
     * Get List of sent emails
     * @param $filters Array | filters for emails listing
     * @return Object
     */
    public function getEmails($filters = [])
    {
        $authUser = Auth::user();
        $user = [];

        if (!ine($filters, 'users')) {
            $user[] = $authUser->id;
		} else {
			$user = $filters['users'];
			if($user != 'all') {
				$user = (array)$user;
			}
        }

        if (ine($filters, 'users') && !$authUser->isAuthority()) {
            $user[] = (array)$authUser->id;
        }

        if ((ine($filters, 'customer_id') || ine($filters, 'job_id') || ine($filters, 'stage_code'))) {
            if($authUser->isSubContractorPrime() && isset($filters['users'])) {
				$user = (array)$filters['users'];
			} else {
				$user = [];
			}
        }
        $filters['users'] = $user;
        $emails = $this->repo->getEmails($filters);
        return $emails;
    }

    /**
     * Get Sent email by id
     * @param $id Integer | Id Associate to email
     * @return Object
     */
    public function getById($id)
    {
        $email = $this->repo->getById($id);
        return $email;
    }

    public function getUnreadEmailCount()
	{
		$emails = $this->repo->getUnreadEmailCount();

		return $emails;
	}

    /**
     * Mark As Read Or Unread
     * @param  array $emailIds | Array of email ids
     * @param  bool $read | Read
     * @return void
     */
    public function markAsRead(array $emailIds, $read = true)
    {
        $conversationIds = Email::whereIn('id', $emailIds)
            ->whereCreatedBy(Auth::id())// only current user
            ->pluck('conversation_id')->toArray();
        // read conversations..
        $emails = $this->repo->make()
            ->whereIn('conversation_id', $conversationIds)
            ->update(['is_read' => $read]);

        Firebase::updateUserEmailCount(Auth::id());
    }

    public function deleteMultipleByIds($ids, $force = false)
	{
		$conversationIds = Email::whereIn('id', (array)$ids)
			->whereCreatedBy(Auth::id())
			->withTrashed()
			->pluck('conversation_id')
            ->toArray();
		$emails = Email::whereIn('conversation_id',$conversationIds)
			->withTrashed();

		if($emails->count()) {
			$emails->with(['jobs']);
			$jobEmails = $emails->groupBy('conversation_id')->get();
			foreach ($jobEmails as $key => $email) {
				foreach ($email->jobs as $key => $job) {
					$job->updateJobUpdatedAt();
				}
			}
		}
		if($force) {
			$emails->forceDelete();
		} else {
			$emails->delete();
		}

		Firebase::updateUserEmailCount(Auth::id());

		return true;
	}

    /***************** Private Section *******************/

    /**
     * Get Attachments files array
     * @param $attachments Array | Array of attachments; type (Resource or Proposal) and values
     * @return Array
     * @access private
     */
    private function getAttachementFiles(array $attachments = [])
    {
        $files = [];
        if (empty($attachments)) {
            return $files;
        }
        foreach ($attachments as $key => $attachment) {
            if (!ine($attachment, 'type') || !ine($attachment, 'value')) {
                throw new InvalideAttachment("Email sending fails. Invalid Attachment.");
            }
            $files[] = $this->getFile($attachment['type'], $attachment['value']);
        }
        return $files;
    }

    /**
     * Get File path for attchment
     * @param $type String | type of attachment file (e.g., resource or proposal)
     * @param $id Int or String | id of resource or proposal
     * @return String (path of file)
     * @access private
     */
    private function getFile($type, $id)
    {
        $fileData = [];
        try {
            switch ($type) {
                case 'resource':
                case 'upload':
                    $resource = $this->resourcesRepo->getFile($id);
                    $fileData['name'] = basename($resource->path);
                    $fileData['path'] = \config('resources.BASE_PATH') . $resource->path;
                    break;

                case 'proposal':
                    $proposal = $this->proposalsRepo->getById($id);
                    $fileData['name'] = 'proposal_' . basename($proposal->file_path);
                    $fileData['path'] = $proposal->getFilePathWithoutUrl();
                    break;
                case 'estimate':
                    $estimate = $this->estimateRepo->getById($id);
                    $fileData['name'] = 'estimate_' . basename($estimate->file_path);
                    $fileData['path'] = \config('jp.BASE_PATH') . $estimate->file_path;
                    break;
                case 'material_list':
                    $materialList = $this->materialListRepo->getById($id);
                    $fileData['name'] = 'material_list_' . basename($materialList->file_path);
                    $fileData['path'] = \config('jp.BASE_PATH') . $materialList->file_path;
                    break;
                case 'workorder':
                    $workOrder = $this->workOrderRepo->getById($id);
                    $fileData['name'] = 'workorder_' . basename($workOrder->file_path);
                    $fileData['path'] = \config('jp.BASE_PATH') . $workOrder->file_path;
                    break;
                case 'invoice':
                    $invoice = JobInvoice::find($id);
                    $token = QuickBooks::getToken();
                    $filePath = config('jp.BASE_PATH') . $invoice->file_path;
                    if (!$invoice->file_size) {
                        $filePath = 'public/' . $invoice->file_path;
                    }

                    $fileData['name'] = 'invoice_' . basename($invoice->file_path);
                    $fileData['path'] = $filePath;

                    if($token && $invoice->quickbook_invoice_id) {
                        QBInvoice::createOrUpdateQbInvoicePdf($invoice, $token);

                        if($invoice->qb_file_path){
                            $filePath = config('jp.BASE_PATH').$invoice->qb_file_path;
                            $fileData['name'] = 'invoice_'.basename($invoice->qb_file_path);
                            $fileData['path'] =  $filePath;
                        }
                    }
                    break;
                case 'worksheet':
                    $worksheet = Worksheet::find($id);
                    $filePath = config('jp.BASE_PATH') . $worksheet->file_path;
                    $fileData['path'] = $filePath;
                    $fileData['name'] = $worksheet->type;
                    break;
                case 'credit':
                    $jobCredit = JobCredit::find($id);
                    $filePath = config('jp.BASE_PATH') . $jobCredit->file_path;
                    $fileData['path'] = $filePath;
                    $fileData['name'] = 'credit_' . basename($jobCredit->file_path);
                    break;
                case 'measurement':
                    $measurement = $this->measurementRepo->getById($id);
                    $fileData['name'] = 'measurement_'.basename($measurement->file_path);
                    $fileData['path'] = config('jp.BASE_PATH').$measurement->file_path;
                    break;
                case 'drip_campaign_email_attachment':
                    $dripCampaign = $this->resourcesRepo->getFile($id);
                    $fileData['name'] = basename($dripCampaign->path);
                    $fileData['path'] = config('resources.BASE_PATH').$dripCampaign->path;
                    break;
                default:
                    goto Invalide;
            }

            /*if($type == 'resource' || $type == 'upload') {
				$resource = $this->resourcesRepo->getFile($id);
				$fileData['name'] = $resource->name;
				$fileData['path'] = \config('resources.BASE_PATH').$resource->path;
			}elseif ($type == 'proposal') {
				$proposal = $this->proposalsRepo->getById($id);
				$fileData['name'] = $proposal->title;
				$fileData['path'] = \config('jp.BASE_PATH').$proposal->file_path;
			}elseif ($type == 'estimate') {
				$estimate = $this->estimateRepo->getById($id);
				$fileData['name'] = $estimate->title;
				$fileData['path'] = \config('jp.BASE_PATH').$estimate->file_path;
			}else {
				goto Invalide;
			}
			}*/

            return $fileData;
        } catch (\Exception $e) {
            Invalide :
            throw new InvalideAttachment("Email sending fails. Invalid Attachment.");
        }
    }

    private function moveAttachments($attachments, $meta = array())
    {
        if (empty($attachments)) {
            return $attachments;
        }
        $rootDir = $this->getRootDir();
        $destination = \config('resources.BASE_PATH') . $rootDir->path;
        $resourcesRepo = App::make(\App\Repositories\ResourcesRepository::class);
        foreach ($attachments as $key => $attachment) {
            $type = $attachment['type'];
            $id = $attachment['value'];

            switch ($type) {
                case 'resource':
                case 'upload':
                    $file = $this->resourcesRepo->getFile($id);
                    $filePath = \config('resources.BASE_PATH') . $file->path;
                    $name = $file->name;
                    $mimeType = $file->mime_type;
                    break;

                case 'proposal':
                    $file = $this->proposalsRepo->getById($id);
                    $filePath = $file->getFilePathWithoutUrl();
                    $name = $file->title;
                    $mimeType = $file->file_mime_type;
                    break;

                case 'estimate':
                    $file = $this->estimateRepo->getById($id);
                    $filePath = \config('jp.BASE_PATH') . $file->file_path;
                    $name = $file->title;
                    $mimeType = $file->file_mime_type;
                    break;

                case 'material_list':
                    $file = $this->materialListRepo->getById($id);
                    $filePath = \config('jp.BASE_PATH') . $file->file_path;
                    $name = $file->title;
                    $mimeType = $file->file_mime_type;
                    break;

                case 'workorder':
                    $file = $this->workOrderRepo->getById($id);
                    $filePath = \config('jp.BASE_PATH') . $file->file_path;
                    $name = $file->title;
                    $mimeType = $file->file_mime_type;
                    break;

                case 'invoice':
                    $invoice = JobInvoice::find($id);
                    $token = QuickBooks::getToken();
                    $filePath = config('jp.BASE_PATH') . $invoice->file_path;
                    if($token && $invoice->quickbook_invoice_id) {
                        QBInvoice::createOrUpdateQbInvoicePdf($invoice, $token);

                        if($invoice->qb_file_path){
                            $filePath = config('jp.BASE_PATH').$invoice->qb_file_path;
                        }
                    }

                    $name = $invoice->title;
                    $mimeType = 'application/pdf';
                    break;

                case 'worksheet':
                    $worksheet = Worksheet::find($id);
                    $filePath = config('jp.BASE_PATH') . $worksheet->file_path;
                    $name = $worksheet->type;
                    $mimeType = 'application/pdf';
                    break;

                case 'credit':
                    $jobCredit = JobCredit::find($id);
                    $filePath = config('jp.BASE_PATH') . $jobCredit->file_path;
                    $name = 'credit_' . basename($jobCredit->file_path);
                    $mimeType = 'application/pdf';
                    break;

                case 'measurement':
                    $measurement = $this->measurementRepo->getById($id);
                    $mimeType = 'application/pdf';
                    $name     =  $measurement->title;
                    $filePath = config('jp.BASE_PATH').$measurement->file_path;
                    break;
                case 'drip_campaign_email_attachment':
                    $dripCampaign = $this->resourcesRepo->getFile($id);
                    $filePath = config('resources.BASE_PATH').$dripCampaign->path;
                    $name = $dripCampaign->name;
                    $mimeType = $dripCampaign->mime_type;
                    break;

                default:
                    throw new InvalideAttachment("Invalid Attachment.");
            }


            /*if($type == 'resource' || $type == 'upload') {
				$file = $this->resourcesRepo->getFile($id);
				$filePath = \config('resources.BASE_PATH').$file->path;
				$name = $file->name;
				$mimeType = $file->mime_type;

			}elseif ($type == 'proposal' || $type == 'estimate') {
				if($type == 'proposal')
					$file = $this->proposalsRepo->getById($id);
				else
					$file = $this->estimateRepo->getById($id);

				$filePath = \config('jp.BASE_PATH').$file->file_path;
				$name = $file->title;
				$mimeType = $file->file_mime_type;
			}else {
				throw new InvalideAttachment("Invalid Attachment.");
			}*/

            // get file extension..
            $extension = File::extension($filePath);

            // get file size
            $size = FlySystem::getSize($filePath);

            // create physical file name..
            $physicalName = Carbon::now()->timestamp . '_' . rand() . '.' . $extension;

            // copy file to attachment directory..
            $resource = $this->resourceService->copy($rootDir, $filePath, $destination, $name, $mimeType, $size, $physicalName, null, $meta);

            $attachments[$key]['ref_id']	= $id;
            $attachments[$key]['value'] = $resource->id;
        }

        return $attachments;
    }

    private function getRootDir()
    {
        $scope = App::make(\App\Services\Contexts\Context::class);
        $parentDir = Resource::name(Resource::EMAIL_ATTACHMENTS)->company($scope->id())->first();
        if (!$parentDir) {
            $root = Resource::companyRoot($scope->id());
            $parentDir = $this->resourceService->createDir(Resource::EMAIL_ATTACHMENTS, $root->id);
        }
        return $parentDir;
    }

    private function uploadAttachment($type, $file)
    {
        $rootDir = $this->getRootDir()->id;
        $resource = $this->resourceService->uploadFile($rootDir, $file);
        return [
            'type' => $type,
            'value' => $resource->id
        ];
    }

    public function getReplyToAddress($email)
    {
        $config = config('mail.imap.reply-to');
        $replyTo = $config['username'];
        $replyTo .= '+' . $config['prefix'];
        $replyTo .= sprintf("%07d", $email->id);
        $replyTo .= '@' . $config['domain'];
        return $replyTo;
    }

    private function maintainActivityLog($data)
    {
        //maintain log for job stage email
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_STAGE_EMAIL_SENT,
            [],
            [],
            $data['customer_id'],
            $data['main_job_id'],
            $data['stage_code'],
            false
        );
    }
}
