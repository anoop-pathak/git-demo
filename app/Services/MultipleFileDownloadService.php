<?php
namespace App\Services;

use App\Repositories\ResourcesRepository;
use App\Exceptions\InvalideAttachment;
use App\Repositories\ProposalsRepository;
use App\Repositories\EstimationsRepository;
use App\Repositories\EmailTemplateRepository;
use App\Models\Email;
use App\Services\Resources\ResourceServices;
use App\Models\Proposal;
use App\Repositories\MaterialListRepository;
use App\Repositories\MeasurementRepository;
use App\Repositories\WorkOrderRepository;
use App\Services\QuickBooks\QuickBookService;
use Aws\DynamoDb\Exception\DynamoDbException;
use App\Exceptions\NotFoundException;
use App\Exceptions\MultipleFileDownloadException;
use Aws\DynamoDb\DynamoDbClient;
use Aws\Credentials\Credentials;
use App\Models\ApiResponse;
use Exception;
use Config;
use FlySystem;
use App\Models\JobInvoice;
use App\Models\Worksheet;
use App\Models\JobCredit;

class MultipleFileDownloadService {

    /**
	 * Resources Repo
	 * @var \JobProgress\Repositories\ResourcesRepository
	 */
	protected $resourcesRepo;

	/**
	 * Proposals Repo
	 * @var \JobProgress\Repositories\ProposalsRepository
	 */
	protected $proposalsRepo;

	/**
	 * Estimates Repo
	 * @var \JobProgress\Repositories\EstimationsRepository
	 */
	protected $estimateRepo;

	/**
	 * MaterialList Repo
	 * @var \JobProgress\Repositories\MaterialListRepository
	 */
	protected $materialListRepo;

	/**
	 * WorkOrder Repo
	 * @var \JobProgress\Repositories\WorkOrderRepository
	 */
	protected $workOrderRepo;

	/**
	 * Resources Service
	 * @var \JobProgress\Resources\ResourceServices
	 */
	protected $resourceService;

	private $credentials;

	private $client;

	private $dynamoDbClient;

	private $s3bucket;

    function __construct(
        ResourcesRepository $resourcesRepo,
        ProposalsRepository $proposalsRepo,
        EstimationsRepository $estimateRepo,
        EmailTemplateRepository $emailTemplateRepo,
        MaterialListRepository $materialListRepo,
        ResourceServices $resourceService,
        MeasurementRepository $measurementRepo,
        WorkOrderRepository $workOrderRepo,
        QuickBookService $quickBookService
    ) {
        $this->resourcesRepo = $resourcesRepo;
		$this->proposalsRepo = $proposalsRepo;
		$this->estimateRepo  = $estimateRepo;
		$this->emailTemplateRepo = $emailTemplateRepo;
		$this->materialListRepo = $materialListRepo;
		$this->measurementRepo  = $measurementRepo;
		$this->resourceService = $resourceService;
		$this->workOrderRepo = $workOrderRepo;
		$this->quickBookService = $quickBookService;

		$this->client = config('flysystem.connections.dynamodb.client');

		$this->s3bucket = config('flysystem.connections.s3.bucket');

		$this->credentials = new Credentials($this->client['credentials']['key'], $this->client['credentials']['secret']);

		$this->dynamoDbClient = DynamoDbClient::factory(array(
			'region' => $this->client['region'],
			'version' => $this->client['version'],
			'credentials' => $this->credentials
		));
	}

	function multilpleDownloads($allFiles)
	{
		$requestId = generateUniqueToken();
		$files = [];

		foreach($allFiles as $file) {
			$files[] = ["S" => $file];
		}

		$result = $this->dynamoDbClient->putItem([
			'Item' => [
				'id' => [
					'S' => (String) $requestId,
				],
				'downloads' => [
					'L' => $files
				],
				'bucket'=> [
					'S' => $this->s3bucket
				],
				'created_at' => [
					'S'=> date('Y-m-d H:i:s')
				]
			],
			'ReturnConsumedCapacity' => 'TOTAL',
			'TableName' => 'downloads',
		]);

		$resultSet = $result->toArray();

		if(ine($resultSet, 'ConsumedCapacity')) {

			return ApiResponse::success([
				'message' => 'Download request saved successfully.',
				'data' => [
					'request_id' => $requestId
				],
			]);
		}

		throw new MultipleFileDownloadException('Unable to process your request.');

    }

    /**
	 * Get File path for attchment
	 * @param $type String | type of attachment file (e.g., resource or proposal)
	 * @param $id Int or String | id of resource or proposal
	 * @return String (path of file)
	 * @access public
	 */
	public function getFile($type, $id) {

		switch($type) {
			case 'resource':
			case 'upload':
				$resource = $this->resourcesRepo->getFile($id);

				return config('resources.BASE_PATH').$resource->path;
			case 'proposal':
				$proposal = $this->proposalsRepo->getById($id);

				return $proposal->getFilePathWithoutUrl();
			case 'estimate':
				$estimate = $this->estimateRepo->getById($id);

				return config('jp.BASE_PATH').$estimate->file_path;
			case 'material_list':
				$materialList = $this->materialListRepo->getById($id);

				return config('jp.BASE_PATH').$materialList->file_path;
			case 'workorder':
				$workOrder = $this->workOrderRepo->getById($id);

				return config('jp.BASE_PATH').$workOrder->file_path;
			case 'invoice':
				$invoice = JobInvoice::findOrFail($id);
				$token = $this->quickBookService->getToken();
				$filePath = config('jp.BASE_PATH').$invoice->file_path;
				if(!$invoice->file_size) {
					$filePath = 'public/'.$invoice->file_path;
				}

				return $filePath;
			case 'worksheet':
				$worksheet = Worksheet::findOrFail($id);

				return config('jp.BASE_PATH').$worksheet->file_path;
			case 'credit':
				$jobCredit = JobCredit::findOrFail($id);

				return config('jp.BASE_PATH').$jobCredit->file_path;
			case 'measurement':
				$measurement = $this->measurementRepo->getById($id);

				return config('jp.BASE_PATH').$measurement->file_path;
			default:
				throw new InvalideAttachment('Invalid attachment');
		}
	}

	 /**
	 * Get zip file status
	 * @param $type String
	 * @return array
	 */

	function getZipFileStatus($requestId = null)
	{
		$timerCount = 0; //Long polling counter

		$response = [
			'id' => $requestId,
			'download_url' => ''
		];

		while($timerCount <= 3) {

			$result = $this->dynamoDbClient->getItem(array(
				'TableName' => 'downloads',
				'Key' => array(
					'id' => array('S' => $requestId)
				)
			));

			$resultSet = $result->toArray();

			if(ine($resultSet, 'Item')) {
				if(ine($resultSet['Item'], 'status')) {
					$response['status'] = $resultSet['Item']['status']['S'];
					$response['download_url'] = null;
					if($response['status'] == 'success') {
						$response['download_url'] = FlySystem::publicUrl($resultSet['Item']['download_url']['S']);
					}
					return ApiResponse::success([
						'data' => $response
					]);

				} else {
					++$timerCount; //Increament the counter for long polling
					sleep(1);
					continue;
				}
			}

			throw new NotFoundException('Request Id not found.');
		}
	}
}