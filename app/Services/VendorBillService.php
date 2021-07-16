<?php
namespace App\Services;

use App\Repositories\VendorBillRepository;
use FlySystem;
use App\Models\VendorBillLine;
use App\Models\VendorBillAttachment;
use App\Models\Resource;
use Illuminate\Support\Facades\View;
use PDF;
use App\Services\AttachmentService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\DB;
use App\Services\Resources\ResourceServices;
use App\Exceptions\NotUpdateVendorBillWithTaxAmount;
use App\Events\AttachmentDeleted;
use App\Exceptions\MinVendorBillAmountException;
use Exception;

class VendorBillService
{
	function __construct(VendorBillRepository $repo, AttachmentService $attachmentService, ResourceServices $resourceService)
	{
		$this->repo = $repo;
		$this->attachmentService = $attachmentService;
		$this->resourceService = $resourceService;
	}

	public function createVendorBills($jobId, $vendorID, $billDate, $lines, $meta = array())
	{
		try {
			DB::beginTransaction();
			$lines = $this->makeLinesObject($lines, $meta);
			$attachments = [];
			if (ine($meta,'attachments')) {
				$attachments = $this->attachmentService->moveAttachments(Resource::VENDOR_BILL_ATTACHMENTS, $meta['attachments']);
			}

			$vendorBillAmount = 0;
			$vendorBillAmount = array_sum(array_map(function($item) {
				return $item['rate'] * $item['quantity'];
			}, $lines));

			if($vendorBillAmount < 0) {
				throw new MinVendorBillAmountException(trans('response.error.least_amount', ['attribute' => 'Bill']));
			}

			//save vendor bill..
			$vendorBill = $this->repo->createVendorBills($jobId, $vendorID, $billDate, $lines, $attachments, $meta);

			$this->createVendorBillPdf($vendorBill);
			DB::commit();
		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}

		return $vendorBill;
	}

	public function updateVendorBills($vendorBill, $vendorID, $billDate, $lines, $meta = array())
	{
		try {
			DB::beginTransaction();
			if ($vendorBill->tax_amount) {
				throw new NotUpdateVendorBillWithTaxAmount("You cannot update vendor bill with tax amount.");
			}
			$lines = $this->makeLinesObject($lines, $meta);
			$attachments = [];
			if (ine($meta,'attachments')) {
				$attachments = $this->attachmentService->moveAttachments(Resource::VENDOR_BILL_ATTACHMENTS, $meta['attachments']);
			}

			$vendorBillAmount = 0;
			$vendorBillAmount = array_sum(array_map(function($item) {
				return $item['rate'] * $item['quantity'];
			}, $lines));

			if($vendorBillAmount < 0) {
				throw new MinVendorBillAmountException(trans('response.error.least_amount', ['attribute' => 'Bill']));
			}

			//update vendor bill..
			$vendorBill = $this->repo->updateVendorBills($vendorBill, $vendorID, $billDate, $lines, $attachments, $meta);
			$this->createVendorBillPdf($vendorBill);

			//delete attachments
			if(ine($meta,'delete_attachments')) {
				VendorBillAttachment::whereIn('value', $meta['delete_attachments'])
					->where('vendor_bill_id', $vendorBill->id)
					->delete();
				$this->resourceService->removeFiles($meta['delete_attachments']);

				Event::fire('JobProgress.Events.AttachmentDeleted', new AttachmentDeleted($meta['delete_attachments']));
			}

			DB::commit();
		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}

		return $vendorBill;
	}

	/**
	 * Make line object
	 * @param  array  $lines Array
	 * @return Lines Object
	 */
	private function makeLinesObject($lines = array(), $input = [])
	{
		$billingLines = [];
		foreach ($lines as $line) {

			$billingLines[] = new VendorBillLine($line);
		}

		return $billingLines;
	}

	public function createVendorBillPdf($vendorBill, $timestampUpdate = true)
	{
		$lines = $vendorBill->lines;
		$company  = $vendorBill->company;

		$oldFilePath = null;
		if(!empty($vendorBill->file_path)) {
			$oldFilePath = config('jp.BASE_PATH').$vendorBill->file_path;
		}

		$fileName =   $vendorBill->id.'_'. timestamp().'.pdf';
		$baseName = 'vendor_bills/'.$fileName;
		$fullPath = config('jp.BASE_PATH') . $baseName;

		$contents = View::make('vendors.vendor_bill',[
			'lines'   => $lines,
			'vendor'  => $vendorBill->vendor,
			'company' => $company,
			'job'	  => $vendorBill->job,
			'vendorBill' => $vendorBill,
			'customer'	 => $vendorBill->customer,
		])->render();

		$pdf = PDF::loadHTML($contents)->setOption('page-size', 'A4')
			->setOption('margin-left', 0)
			->setOption('margin-right', 0)
			->setOption('dpi', 200);

		FlySystem::put($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);

		$vendorBill->file_path = $fullPath;
		$vendorBill->timestamps = $timestampUpdate;
		$vendorBill->update();
		$this->fileDelete($oldFilePath);

		return $vendorBill;
	}

	/**
	 * File delete
	 * @param  url $oldFilePath  Old file Path Url
	 * @return Boolan
	 */
	private function fileDelete($oldFilePath)
	{
		if(!$oldFilePath) return;
		try {
			FlySystem::delete($oldFilePath);
		} catch(Exception $e) {
			// nothing to do.
		}
	}

}