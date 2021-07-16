<?php
namespace App\Http\Controllers;

use Aws\DynamoDb\DynamoDbClient;
use Aws\Credentials\Credentials;
use Aws\DynamoDb\Exception\DynamoDbException;
use App\Services\MultipleFileDownloadService;
use App\Services\Emails\EmailServices;
use App\Exceptions\InvalideAttachment;
use App\Exceptions\NotFoundException;
use App\Exceptions\MultipleFileDownloadException;
use App\Exceptions\MultipleFileDownloadLimitException;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Models\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class MultiDownloadsController extends Controller
{
	protected $repo;
	protected $response;
	private $credentials;
	private $client;
	private $dynamoDbClient;
	private $s3bucket;
	private $emailServices;
	private $downloadService;

	public function __construct(
		EmailServices $emailServices,
		MultipleFileDownloadService $multipleDownloadService
	) {
		parent::__construct();

		$this->client = config('flysystem.connections.dynamodb.client');

		$this->s3bucket = config('flysystem.connections.s3.bucket');

		$this->credentials = new Credentials($this->client['credentials']['key'], $this->client['credentials']['secret']);

		$this->dynamoDbClient = DynamoDbClient::factory(array(
			'region' => $this->client['region'],
			'version' => $this->client['version'],
			'credentials' => $this->credentials
		));

		$this->emailServices = $emailServices;

		$this->downloadService = $multipleDownloadService;

	}

	/**
     *  Initiate the download
	 *  Add download entry in the dynmodb
     */

	function initMultiDownload()
	{
		$input = Request::all();

		try {
			//old code
			$files = [];
			Validator::extend('chkfiles', function($attribute, $value, $parameters) use (&$files) {

				if(is_array($value) && !empty($value)) {

					$isValid = true;

					foreach($value as $file) {
						if(ine($file, 'type') && ine($file, 'id')) {

							$files[] = $this->downloadService->getFile($file['type'], $file['id']);

							if(count($files) > 20 ) {
								throw new MultipleFileDownloadLimitException('You can only download 20 files at once.');
							}

						} else {
							$isValid = false;
						}
					}

					return $isValid;
				}

				return false;
			});

			$validator = Validator::make($input, [
				'file_urls' => 'required|array'
			], [
				'files.chkfiles' => 'Please provide a valid file list.'
			]);

			if ($validator->fails()) {
				return ApiResponse::validation($validator);
			}

			return $this->downloadService->multilpleDownloads($input['file_urls']);

		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound( class_basename($e->getModel()).' Not Found');
		} catch (InvalideAttachment $e) {

			return ApiResponse::errorGeneral($e->getMessage());

		} catch(DynamoDbException $e) {

			return ApiResponse::errorGeneral($e->getMessage());

		} catch(MultipleFileDownloadException $e) {

			return ApiResponse::errorGeneral($e->getMessage());

		} catch(MultipleFileDownloadLimitException $e) {

			return ApiResponse::errorGeneral($e->getMessage());

		}  catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}

	}

	/**
     *  Get Zip file creation status in the dynmodb
     */

	function getStatusMultiDownload($requestId)
	{
		try {
			return $this->downloadService->getZipFileStatus($requestId);

		} catch(DynamoDbException $e) {

			return ApiResponse::errorGeneral($e->getMessage());

		} catch (NotFoundException $e) {

			return ApiResponse::errorNotFound($e->getMessage());

		} catch (\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.something_wrong'), $e);
		}
	}
}