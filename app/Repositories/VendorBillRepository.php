<?php
namespace App\Repositories;

use App\Models\VendorBill;
use App\Services\Contexts\Context;
use App\Models\Vendor;
use App\Models\VendorBillAttachment;
use App\Models\Address;
use App;

Class VendorBillRepository extends ScopedRepository {

	/**
     * The base eloquent VendorBills
     * @var Eloquent
     */
	protected $model;
	protected $scope;
	protected $vendor;
	protected $address;

	function __construct(VendorBill $model, Vendor $vendor, Context $scope, Address $address)
	{
		$this->model   = $model;
		$this->scope   = $scope;
		$this->vendor  = $vendor;
		$this->address = $address;
	}

	public function createVendorBills($jobId, $vendorID, $billDate, $lines, $attachments = array(), $meta)
	{
		$job = App::make('App\Repositories\JobRepository')->getById($jobId);

		$bill['job_id'] = $job->id;
		$bill['vendor_id']  = $vendorID;
		$bill['bill_date']  = $billDate;
		$bill['customer_id']= $job->customer_id;
		$bill['bill_number']= ine($meta,'bill_number') ? $meta['bill_number'] : null;
		$bill['due_date']   = ine($meta,'due_date') ? $meta['due_date'] : null;
		$bill['note']       = ine($meta,'note') ? $meta['note'] : null;
		$bill['address']    = ine($meta,'address') ? $meta['address'] : null;
		$bill['origin']     = isset($meta['origin']) ? $meta['origin'] : 0;
		$bill['tax_amount']    = ine($meta,'tax_amount') ? $meta['tax_amount'] : 0;
		$bill['company_id'] = $this->scope->id();

		$vendorBill = $this->model->create($bill);
		$vendorBill->lines()->saveMany($lines);

		$amount = 0;
		foreach ($vendorBill->lines as $line) {
			$lineAmount = ($line->rate * $line->quantity);

			$amount += $lineAmount;
		}

		$vendorBill->update([
			'total_amount' => $amount,
		]);

		if($attachments) {
			$this->saveAttachments($vendorBill, $attachments);
		}

		return $vendorBill;
	}

	public function getFilteredVendors($filters = array())
	{
		$vendorBills = $this->make()->sortable();
		if(!ine($filters, 'sort_by')) {
			$vendorBills->orderBy('created_at', 'desc');
		}

		$this->applyFilters($vendorBills, $filters);

		return $vendorBills;
	}

	public function updateVendorBills(VendorBill $vendorBill, $vendorID, $billDate, $lines, $attachments = array(),
		$meta = array())
	{
		$vendorBill->vendor_id = $vendorID;
		$vendorBill->bill_date = $billDate;
		$vendorBill->address   = ine($meta,'address') ? $meta['address'] : null;
		$vendorBill->bill_number = ine($meta,'bill_number') ? $meta['bill_number'] : null;
		$vendorBill->due_date = ine($meta,'due_date') ? $meta['due_date'] : null;
		$vendorBill->note = ine($meta,'note') ? $meta['note'] : null;
		$vendorBill->tax_amount = ine($meta,'tax_amount') ? $meta['tax_amount'] : 0;
		$vendorBill->save();
		$vendorBill->lines()->delete();
		$vendorBill->lines()->saveMany($lines);

		$amount = 0;
		foreach ($vendorBill->lines as $line) {
			$lineAmount = ($line->rate * $line->quantity);

			$amount += $lineAmount;
		}

		$vendorBill->update([
			'total_amount' => $amount,
		]);
		if($attachments) {
			$this->saveAttachments($vendorBill, $attachments);
		}

		return $vendorBill;
	}

	private function saveAttachments(VendorBill $vendorBill, array $attachments = array()) {
		foreach ($attachments as $attachment) {
			VendorBillAttachment::create([
				'vendor_bill_id' =>	$vendorBill->id,
				'type'	   =>	$attachment['type'],
				'value'	   =>	$attachment['value'],
			]);
		}
	}

	private function applyFilters($query, $filters = array())
	{
		if(ine($filters, 'job_id')) {
			$query->where('job_id', $filters['job_id']);
		}
	}
}