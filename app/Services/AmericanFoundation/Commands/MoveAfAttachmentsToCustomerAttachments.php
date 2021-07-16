<?php
namespace App\Services\AmericanFoundation\Commands;

use Carbon\Carbon;
use App\Models\CustomerMeta;
use App\Models\Customer;
use App\Models\Resource;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Models\AfAttachment;
use FlySystem;
use Aws\S3\S3Client;
use Settings;

class MoveAfAttachmentsToCustomerAttachments extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move_af_attachments_to_customer_attachments';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Move af attachments to customer attachments.';

	protected $attachmentsDirName = "i360";
	private $sourceDir = "american_foundation/";
	private $sourceFilesFolderPath = "american_foundation_csv/attachments/files/";
	private $destinationFilesFolderPath = "american_foundation_csv/attachments/files-with-extensions/";

	private $inc = 0;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		Config::set('notifications.enabled', false);
        setAuthAndScope(config('jp.american_foundation_system_user_id'));
		setScopeId(config('jp.american_foundation_company_id'));

		$s3 = new S3Client(Config::get('flysystem.connections.s3.client'));
		$bucket = Config::get('flysystem.connections.s3.bucket');

		$this->resourceService = App::make('App\Services\Resources\ResourceServices');
		$this->resourceRepository = App::make('App\Repositories\ResourcesRepository');


        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ': Start move af attachments to Jp Resources.');
        AfAttachment::with([
                'afCustomer' => function($q) {$q->select('id', 'af_id', 'customer_id');}
			])
			->join('af_customers', function($join) {
				$join->on('af_customers.af_id', '=', 'af_attachments.parent_id');
			})
			/*->whereIn('af_attachments.af_id', [
				"00P1U00000643P5UAI", "00P1U0000064R3WUAU", '00P1U0000064R6gUAE',
				'00P1U0000064R6lUAE', '00P1U0000064R6qUAE', '00P1U0000064R6vUAE',
				'00P1U0000064R70UAE', '00P1U0000064R71UAE', '00P1U000006529RUAQ'])*/
			// ->where('parent_id', 'LIKE', '%a0t1U%')
			// ->where('parent_id', 'a0t1U000001cCwjQAE')
			->select('af_attachments.*')
			->chunk(1000, function($items) use($s3, $bucket) {
				foreach ($items as $item) {
					if($item->attachment_id) {
						$this->inc++;
                        if($this->inc %100 == 0) {
                            $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing attachments:- " . $this->inc);
						}
						continue;
					}

					$customer = $item->afCustomer;
					if(!$customer || !$customer->customer_id) {
                        continue;
					}

					$customerId = $customer->customer_id;
					$parentDir = $this->getParentDir($customerId);
					$sourceFileWithPath = $this->sourceDir . $item->af_id;
					$mimeType = $item->content_type;

					$ex = FlySystem::exists($sourceFileWithPath);
					if(!$ex) {
						continue;
					}

					try {
						$meta = [];
						$originalName = $item->name;
						$fullPath = config('resources.BASE_PATH').$parentDir->path;
						$physicalName = generateUniqueToken().'_'.$item->name;
						$mimeType = $item->content_type;
						$size = FlySystem::getSize($sourceFileWithPath);

						$destinationFileWithPath = $fullPath . '/' . $physicalName;

						if (in_array($mimeType, config('resources.image_types'))) {
							// $file = FlySystem::read($sourceFileWithPath);
							// $this->generateThumb($destinationFileWithPath, $file);

							$s3->copyObject(array(
								// Bucket is required
								'Bucket' => $bucket,
								// CopySource is required
								'CopySource' =>  $bucket. '/'.$sourceFileWithPath,
								// Key is required
								'Key' => $destinationFileWithPath,
								'MetadataDirective' => 'COPY'
							));
							// FlySystem::copy($sourceFileWithPath, $destinationFileWithPath, ['ContentType' => $mimeType]);
							$meta['thumb_exists'] = false;
						} else {
							$s3->copyObject(array(
								// 'ACL' => 'private',
								// Bucket is required
								'Bucket' => $bucket,
								// CopySource is required
								'CopySource' =>  $bucket. '/'.$sourceFileWithPath,
								// Key is required
								'Key' => $destinationFileWithPath,
								'MetadataDirective' => 'COPY'
							));
							// FlySystem::copy($sourceFileWithPath, $destinationFileWithPath, ['ContentType' => $mimeType]);
						}

						$resource = $this->resourceRepository->createFile($originalName, $parentDir, $mimeType, $size, $physicalName, null, $meta);
						$item->attachment_id = $resource->id;
						$item->save();

						$this->inc++;
                        if($this->inc %100 == 0) {
                            // $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing attachments:- " . $this->inc . " , Size:- " . $size . " , MimeType:- " . $mimeType . " , File Name:- " . $physicalName);
                            $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing attachments:- " . $this->inc);
						}

					} catch (\Exception $e) {
					   	Log::error(Carbon::now()->format('Y-m-d H:i:s') . ": Error in American Foundation Move AfAttachments to attachments table");
                        Log::error($e);
                    }
                }
        });
        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processed attachments:- " . $this->inc);
	}

	private function getParentDir($customerId)
	{
		$customer = Customer::find($customerId);
		$resource = $customer->customerMeta()->where('meta_key', CustomerMeta::META_KEY_RESOURCE_ID)->first();
		$resourceId = $resource ? $resource->meta_value : null;
		if(!$resourceId) {

			$resourceId = $this->findOrCreateResource($customer);
		}

		// check is directory is already exists or not.
		if(!$dir = $this->resourceService->getDirWithName($this->attachmentsDirName, $resourceId)) {
			$dir = $this->resourceService->createDir($this->attachmentsDirName, $resourceId, true);
		}

		return $dir;
	}

	/**
     * Create customer default resources.
     *
     * @param Integer $customerId
     * @return void
     */
	private function findOrCreateResource($customer)
	{
		$parentDir = Resource::name('Customers')->company($customer->company_id)->first();

        if (!$parentDir) {
            $rootDir = Resource::company($customer->company_id)->whereNull('parent_id')->where('is_dir', 1)->first();

            // create Customer directory at root level of company if root dir exists.
            if ($rootDir) {
                $parentDir = $this->resourceService->createDir('Customers', $rootDir->id, true);
            }
        }

        if(!$parentDir) {
            return null;
        }

        $resource = Resource::company($customer->company_id)
                            ->where('name', $customer->id)
                            ->where('parent_id', $parentDir->id)
                            ->where('is_dir', 1)
                            ->first();
        if(!$resource) {
            $resource = $this->resourceService->createDir($customer->id, $parentDir->id,true);
        }
        $customer->createOrUpdateMeta(CustomerMeta::META_KEY_RESOURCE_ID, $resource->id);

        if($resource->allChildren()->count() > 0) {
            return $resource->id;
        }

        $customerResources = Settings::get('CUSTOMER_RESOURCES');

        foreach ($customerResources as $customerResource) {

            // check is directory is already exists or not.
            if($this->resourceService->isDirExistsWithName($customerResource['name'], $resource->id)) {
                continue;
            }

			if(isTrue($customerResource['locked'])){
				$photoDir = $this->resourceService->createDir($customerResource['name'], $resource->id, true);
				$customer->createOrUpdateMeta(CustomerMeta::META_KEY_DEFAULT_PHOTO_DIR, $photoDir->id);
			}else {
				$this->resourceService->createDir($customerResource['name'], $resource->id);
			}
        }

        return $resource->id;
	}

	/**
	 * generate thumb of images
	 * @param  $thumbPath
	 * @param  $fileContent
	 * @return boolean
	 */
	public function generateThumb($filePath, $fileContent)
	{
		$fullPathThumb	= preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $filePath);
		$image = \Image::make($fileContent)->orientate();
		if($image->height() > $image->width()) {
			$image->heighten(200, function($constraint) {
				$constraint->upsize();
			});
		}else {
			$image->widen(200, function($constraint) {
				$constraint->upsize();
			});
		}

		FlySystem::put($fullPathThumb, $image->encode()->getEncoded());

		return true;
	}
}
