<?php

namespace App\Services\SRS;

use App\Models\FinancialProduct;
use App\Services\SRS\ImportCustomerProducts;
use App\Models\CompanySupplier;
use App\Models\SupplierBranch;
use Exception;
use GuzzleHttp\Client;
use App\Models\SrsShipToAddress;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\FinancialMacro;
use JobQueue;
use App\Models\QueueStatus;


class SRSQueueHandler
{
    protected $branchData = [];
    public function __construct()
    {
        $this->headers = [
            'client_id'     => config('srs.client_id'),
            'client_secret' => config('srs.client_sec')
        ];
        $this->request = new Client(['headers' => $this->headers]);
    }
    public function updateProductPrice($job, $queueData)
    {
        return $job->delete();
        try {
            $data = json_decode($queueData['data'], true);
            $productList = $data['products'];
            foreach ($productList as $key => $value) {
                FinancialProduct::whereSupplierId($data['supplier_id'])
                    ->whereCode($value['item_code'])
                    ->where('branch_code', $data['branch_code'])
                    ->where('company_id', $data['company_id'])
                    ->update([
                        'unit_cost' => ($value['price'] / $value['unit_conversion_factor']),
                        // 'unit'      => $value['unit'],
                    ]);
            }

            $job->delete();
        } catch (Exception $e) {
            Log::info('SRS Queue Handler Update Product Price Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());

            if($job->attempts() > 3) {
				return $job->delete();
			}
        }
    }
    public function saveSRSDetail($queueJob, $data)
    {
        try {
            $parentQueue = JobQueue::statusInProcess(JobQueue::CONNECT_SRS, $data['company_supplier_id'], $queueJob->attempts());
            $shipToAddrData = [];
            $companySupplier = CompanySupplier::find($data['company_supplier_id']);

            if(!$companySupplier) {
                goto end;
            }
            setScopeId($companySupplier->company_id);
            // get ship to sequence id
            $shipToAddress = $this->request->get(config('srs.customer_base_url').'customers/'.$companySupplier->srs_account_number.'/shipToAddresses');

            $shipToAddresses = json_decode($shipToAddress->getBody(), true);

            if (!ine($shipToAddresses, 'shipToList')){
                goto end;
            }
            foreach ($shipToAddresses['shipToList'] as $key => $address) {
                $shipToAddrData['ship_to_id'][]          = $address['shipToId'];
                $shipToAddrData['ship_to_sequence_id'][] = $address['shipToSequenceId'];
                $this->saveShipToAddress($companySupplier, $address);
            }

            $shipToaddresses = SrsShipToAddress::where('company_id', $companySupplier->company_id)
                ->where('company_supplier_id', $companySupplier->id)
                ->get();
            foreach ($shipToaddresses as $address) {
                $this->saveEligibleBrances($address, $companySupplier);
            }

            $branches = SupplierBranch::where('company_id', $companySupplier->company_id)
                ->where('company_supplier_id', $companySupplier->id)
                ->get();
            $branchCount = $branches->count();
            foreach ($branches as $branch) {
                $this->importProducts($companySupplier, $branch, $parentQueue, $branchCount);
            }
            end:
            // we are updating status to completed in sub queues
            // JobQueue::statusCompleted(JobQueue::CONNECT_SRS, $data['company_supplier_id']);

            return $queueJob->delete();
        } catch (Exception $e) {
            Log::warning($e);
            throw $e;
            $companyId  = null;
            $supplierId = null;
            if(isset($companySupplier)) {
                $companyId  = $companySupplier->company_id;
                $supplierId = $companySupplier->id;
            }
            $errMsg = 'SRS Queue Handler Save SRS Detail Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine(). "\nFor Company Id : {$companyId} And Company Supplier Id : {$supplierId}";

			JobQueue::saveErrorDetail(JobQueue::CONNECT_SRS, $data['company_supplier_id'], $errMsg, $companyId);

            if($queueJob->attempts() > 3) {
                JobQueue::statusFailed(JobQueue::CONNECT_SRS, $data['company_supplier_id'], $companyId);
                return $queueJob->delete();
			}
        }
    }

    public function updateSRSProducts($queueJob, $data)
	{
		try {
			$parentQueue = JobQueue::statusInProcess(JobQueue::SRS_SYNC_DETAILS, $data['company_supplier_id'], $queueJob->attempts());

			$shipToAddrData	= [];
			$companySupplier = CompanySupplier::find($data['company_supplier_id']);
			if(!$companySupplier) {
				goto end;
			}

			setScopeId($companySupplier->company_id);

			// get ship to sequence id
			$shipToAddress = $this->request->get(config('srs.customer_base_url').'customers/'.$companySupplier->srs_account_number.'/shipToAddresses');
			$shipToAddresses = $shipToAddress->json();
			if(!ine($shipToAddresses, 'shipToList')){
				goto end;
			}

			foreach ($shipToAddresses['shipToList'] as $key => $address) {
				$shipToAddrData['ship_to_id'][]			 = $address['shipToId'];
				$shipToAddrData['ship_to_sequence_id'][] = $address['shipToSequenceId'];
				$this->saveShipToAddress($companySupplier, $address);
			}

			// delete in active ship to address
			if(ine($shipToAddrData, 'ship_to_id') && ine($shipToAddrData, 'ship_to_sequence_id')) {
				SrsShipToAddress::where('company_id', $companySupplier->company_id)
					->where('company_supplier_id', $companySupplier->id)
					->where(function($query) use($shipToAddrData) {
						$query->whereNotIn('ship_to_id', $shipToAddrData['ship_to_id'])
							->orWhereNotIn('ship_to_sequence_id', $shipToAddrData['ship_to_sequence_id']);
					})
					->delete();
			}

			$shipToaddresses = SrsShipToAddress::where('company_id', $companySupplier->company_id)
				->where('company_supplier_id', $companySupplier->id)
				->get();

			foreach ($shipToaddresses as $address) {
				$this->saveEligibleBrances($address, $companySupplier);
			}

			$branchesDeleted = false;
			// delete inactive branches
			if(ine($this->branchData, 'branch_id') && ine($this->branchData, 'branch_code')) {
				$branchesDeleted = SupplierBranch::where('company_id', $companySupplier->company_id)
					->where('company_supplier_id', $companySupplier->id)
					->where(function($query) {
						$query->whereNotIn('branch_code', $this->branchData['branch_code'])
							->orWhereNotIn('branch_id', $this->branchData['branch_id']);
					})
					->delete();
			}

			$branches = SupplierBranch::where('company_id', $companySupplier->company_id)
				->where('company_supplier_id', $companySupplier->id)
				->get();

			$branchCount = $branches->count();

			foreach ($branches as $branch) {
				$this->importProducts($companySupplier, $branch, $parentQueue, $branchCount);
			}

			end:

			// we are updating status to completed in sub queues
			// JobQueue::statusCompleted(JobQueue::SRS_SYNC_DETAILS, $data['company_supplier_id']);

			if($branchesDeleted) {
				DB::table('financial_products')
					->where('company_id', $companySupplier->company_id)
					->where('supplier_id', $companySupplier->suppliers->id)
					->whereNotIn('branch_code', $this->branchData['branch_code'])
					->update([
						'deleted_at' => Carbon::now()->toDateTimeString()
					]);
			}

			return $queueJob->delete();
		} catch (Exception $e) {
			Log::warning($e);
			$companyId  = null;
			$supplierId = null;
			if(isset($companySupplier)) {
				$companyId  = $companySupplier->company_id;
				$supplierId = $companySupplier->id;
			}

			$errMsg = 'SRS Queue Handler Update SRS Products Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine(). "\nFor Company Id : {$companyId} And Company Supplier Id : {$supplierId}";

			JobQueue::saveErrorDetail(JobQueue::SRS_SYNC_DETAILS, $data['company_supplier_id'], $errMsg, $companyId);

			if($queueJob->attempts() > 3) {
				JobQueue::statusFailed(JobQueue::SRS_SYNC_DETAILS, $data['company_supplier_id'], $companyId);

				return $queueJob->delete();
			}
		}
	}

    public function saveEligibleBrances($shipToAddress, $companySupplier)
    {
        $eligibleBranches = $this->request->get(config('srs.branch_base_url').'branch/customer/'.$companySupplier->srs_account_number.'?shipToSequenceNumber='.$shipToAddress->ship_to_sequence_id);
        $branchList = json_decode($eligibleBranches->getBody(), true)['branchList'];
        foreach ($branchList  as $key => $branch) {
            $this->saveBranch($companySupplier, $shipToAddress, $branch);
        }
    }
    public function saveBranch($companySupplier, $shipToAddress, $branchDetail)
    {
        $shipToBranches = [];
        try {
            $branchRes = $this->request->get(config('srs.branch_base_url').'branch/'.$branchDetail['branchId']);
            $branchRes = json_decode($branchRes->getBody(), true);
            if(!ine($branchRes, 'branchId') || !ine($branchRes, 'branchCode')) return false;
            $branch = SupplierBranch::firstOrNew([
                'company_id'            => $companySupplier->company_id,
                'company_supplier_id'   => $companySupplier->id,
                'branch_id'             => $branchRes['branchId'],
                'branch_code'           => $branchRes['branchCode']
            ]);
            $branch->name                = ine($branchRes, 'branchName') ? $branchRes['branchName'] : null;
            $branch->address             = ine($branchRes, 'branchAddress') ? $branchRes['branchAddress'] : null;
            $branch->city                = ine($branchRes, 'branchCity') ? $branchRes['branchCity'] : null;
            $branch->state               = ine($branchRes, 'branchState') ? $branchRes['branchState'] : null;
            $branch->zip                 = ine($branchRes, 'branchZip') ? $branchRes['branchZip'] : null;
            $branch->email               = ine($branchRes, 'branchManagerEmail') ? $branchRes['branchManagerEmail'] : null;
            $branch->phone               = ine($branchRes, 'branchPhone') ? $branchRes['branchPhone'] : null;
            $branch->manager_name        = ine($branchRes, 'branchManager') ? $branchRes['branchManager'] : null;
            $branch->logo                = ine($branchRes, 'branchLogo') ? $branchRes['branchLogo'] : null;
            $branch->lat                 = ine($branchRes, 'latitude') ? $branchRes['latitude'] : null;
            $branch->long                = ine($branchRes, 'longitude') ? $branchRes['longitude'] : null;
            $branch->meta                = $branchRes;
            $branch->save();
            if(!in_array($shipToAddress->id, $branch->srsShipToAddresses->pluck('id')->toArray())) {
                $branch->srsShipToAddresses()->attach((array) $shipToAddress->id);
            }
            $this->branchData['branch_code'][]  = $branchRes['branchCode'];
            $this->branchData['branch_id'][]    = $branchRes['branchId'];
        } catch (Exception $e) {
            throw $e;
        }
        return $branch;
    }
    public function importProducts($companySupplier, $branch, $parentQueue, $branchCount = 0)
	{
		try {
			$queueData = [
				'company_supplier_id' => $companySupplier->id,
				'branch_id' => $branch->id,
				'branch_code' => $branch->branch_code,
				'parent_queue_id' => $parentQueue->id,
				'parent_queue_action' => $parentQueue->action,
				'sub_queue_count' => $branchCount,
			];
			JobQueue::enqueue(JobQueue::SRS_SAVE_BRANCH_PRODUCT, $branch->company_id, $branch->id, $queueData);
		} catch (Exception $e) {
			throw $e;
		}
    }

    public function saveBranchProducts($queueJob, $data)
	{
		try {
			JobQueue::statusInProcess(JobQueue::SRS_SAVE_BRANCH_PRODUCT, $data['branch_id'], $queueJob->attempts());

			$companySupplier = CompanySupplier::find($data['company_supplier_id']);

			if(!$companySupplier){
				goto end;
			}

			$branch = SupplierBranch::where('company_id', $companySupplier->company_id)
				->where('id', $data['branch_id'])
				->where('branch_code', $data['branch_code'])
				->first();

			if(!$branch){
				goto end;
			}

			setScopeId($companySupplier->company_id);

			$importProducts = new ImportCustomerProducts($companySupplier);
			$importProducts->import($branch);

			end:

			JobQueue::statusCompleted(JobQueue::SRS_SAVE_BRANCH_PRODUCT, $branch->id);

			// update paent queue status
			if(isset($data['parent_queue_id'])) {
				$subQueues = QueueStatus::where('parent_id', $data['parent_queue_id'])
					->whereIn('status', [JobQueue::STATUS_COMPLETED, JobQueue::STATUS_FAILED,])
					->count();

				if($subQueues == $data['sub_queue_count']) {
					JobQueue::statusCompleted($data['parent_queue_action'], $data['company_supplier_id']);

					// update company supplier updated_at for srs product sync cron
					if(isset($companySupplier)) {
						$companySupplier->updated_at = Carbon::now();
						$companySupplier->save();
					}
				}
			}

			return $queueJob->delete();
		} catch (Exception $e) {
			Log::warning($e);

			try {
				if(!isset($companySupplier)) {
					$companySupplier = null;
				}
				$this->updateBranchProductQueue($queueJob, $data, $companySupplier, $e);
			} catch (Exception $exception) {
				Log::warning($exception);
				$this->updateBranchProductQueue($queueJob, $data, $companySupplier, $exception);
			}
		}
	}

    public function testQueue($queueJob, $data)
    {
        log::info("Queue Test 100");
        $queueJob->delete();
    }
    /************** Private Methods ***************/
    private function saveShipToAddress($companySupplier, $detail)
    {
        $address = SrsShipToAddress::firstOrNew([
            'company_id'            => $companySupplier->company_id,
            'company_supplier_id'   => $companySupplier->id,
            'ship_to_id'            => $detail['shipToId'],
            'ship_to_sequence_id'   => $detail['shipToSequenceId'],
        ]);
        $address->city          = $detail['city'];
        $address->state         = $detail['state'];
        $address->zip_code      = $detail['zipCode'];
        $address->address_line1 = $detail['addressLine1'];
        $address->address_line2 = $detail['addressLine2'];
        $address->address_line3 = $detail['addressLine3'];
        $address->meta          = $detail;
        $address->save();
        return $address;
    }

    /**
	 * update queue details of update branch products job
	 * @param  Queue 			| $queueJob        | Queue Job
	 * @param  Array 			| $data            | Queue Data
	 * @param  CompanySupplier 	| $companySupplier | Object of CompanySupplier
	 * @param  Exception 		| $exception       | Exception object
	 * @return boolean
	 */
	private function updateBranchProductQueue($queueJob, $data, $companySupplier, $exception)
	{
		$companyId  = null;
		$supplierId = null;
		if($companySupplier) {
			$companyId  = $companySupplier->company_id;
			$supplierId = $companySupplier->id;
		}
		$errMsg = 'SRS Queue Handler Save Branch Product Error :'.$exception->getMessage().' in file '.$exception->getFile().' on line number '.$exception->getLine(). "\nFor Company Id : {$companyId} And Company Supplier Id : {$supplierId}";


		JobQueue::saveErrorDetail(JobQueue::SRS_SAVE_BRANCH_PRODUCT, $data['branch_id'], $errMsg, $companyId);
		if($queueJob->attempts() > 3) {
			JobQueue::statusFailed(JobQueue::SRS_SAVE_BRANCH_PRODUCT, $data['branch_id'], $companyId);

			// update paent queue status
			if(isset($data['parent_queue_id'])) {
				$subQueues = QueueStatus::where('parent_id', $data['parent_queue_id'])
				->whereIn('status', [JobQueue::STATUS_COMPLETED, JobQueue::STATUS_FAILED,])
				->count();

				if($subQueues == $data['sub_queue_count']) {
					JobQueue::statusCompleted($data['parent_queue_action'], $data['company_supplier_id']);

					// update company supplier updated_at for srs product sync cron
					if(isset($companySupplier)) {
						$companySupplier->updated_at = Carbon::now();
						$companySupplier->save();
					}
				}
			}

			return $queueJob->delete();
		}
	}
}