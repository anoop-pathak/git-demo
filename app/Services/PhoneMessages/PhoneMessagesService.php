<?php
namespace App\Services\PhoneMessages;

use App\Exceptions\PhoneMessageException;
use App\Repositories\PhoneMessageRepository;
use App\Services\Resources\ResourceServices;
use App\Repositories\ResourcesRepository;
use App\Repositories\MaterialListRepository;
use App\Repositories\MeasurementRepository;
use App\Repositories\WorkOrderRepository;
use App\Repositories\ProposalsRepository;
use App\Repositories\EstimationsRepository;
use App\Services\QuickBooks\QuickBookService;
use App\Services\Twilio\TwilioService;
use FlySystem;
use App\Models\JobInvoice;
use App\Models\Worksheet;
use App\Models\JobCredit;
use Firebase;
use App\Services\Twilio\TwilioNumberService;
use QuickBooks;
use App\Services\Messages\MessageService;
use App\Models\MessageThread;
use App\Repositories\TwilioNumberRepository;

class PhoneMessagesService
{
	/**
	 * Phone Message Repo
	 * @var \App\Repositories\PhoneMessageRepository
	 */
	protected $repo;

	/**
	 * MeasurementRepo
	 * @var \App\Repositories\MeasurementRepository;
	 */
	protected $measurementRepo;

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
	 * Quickbooks Service
	 * @var \App\Services\Resources\QuickBookService
	 */
	protected $quickBookService;

	/**
	 * Twilio Service
	 * @var \App\Services\Twilio\TwilioService
	 */
	protected $twilioService;

	/**
	 * Twilio Number Service
	 * @var \JobProgress\Twilio\TwilioNumberService
	 */
	protected $twilioNumberService;

	/**
	 * Message Service
	 * @var JobProgress\Message\MessageService
	 */
	protected $messageService;

	public function __construct(
		PhoneMessageRepository $repo,
		ResourcesRepository $resourcesRepo,
		ProposalsRepository $proposalsRepo,
		EstimationsRepository $estimateRepo,
		MaterialListRepository $materialListRepo,
		WorkOrderRepository $workOrderRepo,
		QuickBookService $quickBookService,
		MeasurementRepository $measurementRepo,
		TwilioService $twilioService,
		TwilioNumberService $twilioNumberService,
		MessageService $messageService,
		TwilioNumberRepository $twilioNumberRepository
	)
	{
		$this->repo 				= $repo;
		$this->resourcesRepo 		= $resourcesRepo;
		$this->proposalsRepo 		= $proposalsRepo;
		$this->estimateRepo 		= $estimateRepo;
		$this->materialListRepo  	= $materialListRepo;
		$this->workOrderRepo 		= $workOrderRepo;
		$this->quickBookService 	= $quickBookService;
		$this->measurementRepo 		= $measurementRepo;
		$this->twilioService 		= $twilioService;
		$this->twilioNumberService  = $twilioNumberService;
		$this->messageService 		= $messageService;
		$this->twilioNumberRepository = $twilioNumberRepository;
	}

	/**
	 * Get thread list
	 * @param  array  $filters array
	 * @return querybuilder
	 */
	public function getThreadList($filters = array())
	{
		$thread = $this->messageService->getThreadList($filters);

		return $thread;
	}

	/**
	 * Get messge by thread id
	 * @param  Instance $thread   thrad
	 * @param  array    $filters  array
	 * @return messages
	 */
	public function getThreadMessages($thread, $filters = array())
	{
		return $this->messageService->getThreadMessages($thread, $filters);
	}

	/**
	 * Get thread by user id
	 * @param  int $threadId  thread id
	 * @return response
	 */
	public function getThreadById($threadId)
	{
		return $this->messageService->getThreadById($threadId);
	}

	/**
	 * Send Message to Customer
	 * @param  $company
	 * @param  $phoneNumber
	 * @param  $message
	 * @param  array  $metaData
	 * @return message
	 */
	public function send($company, $phoneNumber, $message, $metaData = array())
	{
		$code = config('mobile-message.country_code.'.$company->country->code);
		if(!$code) return false;

		$number = $code.ltrim($phoneNumber, '0');

		if(ine($metaData, 'media')) {
			$mediaPaths = $this->moveMedia($metaData['media'], $company->id, $metaData);
			foreach($mediaPaths as $key => $mediaPath) {
				$shortUrl = Firebase::getShortUrl($mediaPath['file_url']);
				$mediaUrls[$key]  = $shortUrl['shortLink'];
				$metaData['media_urls'][$key]['short_url'] 	=   $shortUrl['shortLink'];
				$metaData['media_urls'][$key]['file_url'] 	= $mediaPath['file_url'];
			}
			$message .= ' '.implode( ' ' , array_map('strval', $mediaUrls));
		}

		$data = [
			'phoneNumber'  => $number,
			'message'      => $message,
		];

		return $this->sendMessage($number, $data, $metaData, $code);
	}

	/**
	 * Save Reply in DB coming from Twilio
	 * @param  $input
	 * @return message
	 */
	public function saveReplyFromTwilio($input = array())
	{
		$phoneNumber = $input['from'];
		$userNumber = $input['to'];

		$userId = $this->repo->getUserIdOfTwilioNumber($userNumber);
		if (!$userId) {
			return;
		}

		$thread = $this->repo->getParentMessageForReply($phoneNumber, $userId);
		if(!$thread) return;

		$messageData = $this->setResponsePayload($input, $thread->messages->last());
		$message = $this->repo->createMessages($messageData, $thread->id);

		return $message;
	}

	/**
	 * Send message
	 * @param  array       $data Array
	 * @return $message
	 */
	public function sendMessage($number, $data = array(), $metaData = array(), $code)
	{
		$fromNumber = $this->twilioNumberService->getFromNumber();
		$configNumber = config('mobile-message.from_address');

		if(!$fromNumber && !$configNumber) {
			throw new PhoneMessageException(trans('response.error.message_not_sent'));
		}

		$message = null;
		$bodyData = [
	        'from'     => $fromNumber ? $fromNumber->phone_number : $configNumber,
	        'body'     => $data['message'],
	        'statusCallback' => config('mobile-message.twilio_message_response'),
		];

		// create a new message thread in message_thread table for a new message if thread id is not in input
		$threadId = ine($metaData, 'thread_id') ? $metaData['thread_id'] : null;
		if ($threadId) {
			$valideThreadId = $this->repo->checkThreadIsVadileOrNot($threadId, $number);
			$threadId = $valideThreadId;
		}

		if(!$threadId) {
			$withoutCodeNumber = substr($number, strlen($code));
			$participants = $this->repo->getParticipants($withoutCodeNumber);
			$metaData['type'] = MessageThread::TYPE_SMS;
			$metaData['phone_number'] = $number;
			$thread = $this->messageService->createSmsThread($participants, null, $metaData);

			$threadId = $thread->id;
		}

		// sand message to user using twilio service
		$messageData = $this->twilioService->sendMessage($data['phoneNumber'], $bodyData);
		$messageData = $messageData->toArray();
		$messageData['sender_id'] = $metaData['sender_id'];
		$message = $this->repo->createMessages($messageData, $threadId, $metaData);

		return $message;
	}


	/************ Private Functions ******************/
	private function moveMedia($media, $companyId)
	{
		if(empty($media)) return $media;

		foreach ($media as $key => $med) {
			switch($med['type']) {
				case 'resource':
				case 'upload':
					$file = $this->resourcesRepo->getFile($med['value']);
					$filePath = config('resources.BASE_PATH').$file->path;
					break;

				case 'proposal':
					$file = $this->proposalsRepo->getById($med['value']);
					$filePath = $file->getFilePathWithoutUrl();
					break;

				case 'estimate':
					$file = $this->estimateRepo->getById($med['value']);
					$filePath = config('jp.BASE_PATH').$file->file_path;
					break;

				case 'material_list':
					$file = $this->materialListRepo->getById($med['value']);
					$filePath = config('jp.BASE_PATH').$file->file_path;
					break;

				case 'workorder':
					$file = $this->workOrderRepo->getById($med['value']);
					$filePath = config('jp.BASE_PATH').$file->file_path;
					break;

				case 'invoice':
					$invoice = JobInvoice::find($med['value']);
					$token = QuickBooks::getToken();
					$filePath = config('jp.BASE_PATH').$invoice->file_path;
					if($token) {
						$updatedInvoice = true;
						if(!$invoice->qb_file_path){
							$updatedInvoice = QBInvoice::createOrUpdateQbInvoicePdf($invoice, $token);
						}
						if($updatedInvoice) {
							$filePath = config('jp.BASE_PATH').$invoice->qb_file_path;
						}
					}
					break;

				case 'worksheet':
					$worksheet = Worksheet::find($med['value']);
					$filePath  = config('jp.BASE_PATH').$worksheet->file_path;
					break;

				case 'credit':
					$jobCredit = JobCredit::find($med['value']);
					$filePath  = config('jp.BASE_PATH').$jobCredit->file_path;
					break;

				case 'measurement':
					$measurement = $this->measurementRepo->getById($med['value']);
					$filePath = config('jp.BASE_PATH').$measurement->file_path;
					break;

				default:
					throw new PhoneMessageException("Invalid Media.");
			}

			$fileName =  generateUniqueToken().'_'.basename($filePath);

			$destination = "{$companyId}/$fileName";

			$config['ACL'] = 'public-read';

			$isFileExists = FlySystem::setSecondConnection('s3_attachments')->exists($filePath);

			if(!$isFileExists){
				throw new PhoneMessageException("Invalid Media.");
			}

			FlySystem::setSecondConnection('s3_attachments')->copy($filePath, $destination, $config);

			$url = FlySystem::connection('s3_attachments')->getUrl($destination);
			$media[$key]['file_url']	= $url;
		}

		return $media;
	}

	/**
	 * set Payload to Save response in DB
	 *
	 * @param $input
	 * @param $parentMessage
	 * @return array of payload data.
	 */
	private function setResponsePayload($input, $parentMessage)
	{
		$payload = [
			'company_id' 	=> $parentMessage ? $parentMessage->company_id : null,
			'customer_id' 	=> $parentMessage ? $parentMessage->customer_id : null,
			'sender_id'		=> null,
			'sid'			=> $input['sid'],
			'from'			=> $input['from'],
			'to'			=> $input['to'],
			'message' 		=> $input['message'],
			'status' 		=> $input['status'],
			'media_urls'	=> $input['media_urls'],
		];

		return $payload;
	}
}