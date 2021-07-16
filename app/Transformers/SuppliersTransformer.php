<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SuppliersTransformer extends TransformerAbstract
{
    /**
    * List of resources to automatically include
    *
    * @var array
    */
    protected $defaultIncludes = [];
    /**
    * List of resources possible to include
    *
    * @var array
    */
    protected $availableIncludes = ['supplier_branches', 'queue_status'];

    public function transform($supplier)
    {
        return [
            'id' => $supplier->id,
            'name' => $supplier->name,
            'company_id' => $supplier->company_id,
            'branch_id' => $supplier->companySupplier ? $supplier->companySupplier->branch_id : null,
            'branch_name' => $supplier->companySupplier ? $supplier->companySupplier->branch : null,
            'branch_code' => $supplier->companySupplier ? $supplier->companySupplier->branch_code : null,
            'branch_address' => $supplier->companySupplier ? $supplier->companySupplier->branch_address : null,
            'manager_name' => $supplier->companySupplier ? $supplier->companySupplier->manager_name : null,
            'email' => $supplier->companySupplier ? $supplier->companySupplier->email : null,
            'phone' => $supplier->companySupplier ? $supplier->companySupplier->phone : null,
            'ship_to_address' => $supplier->companySupplier ? json_decode($supplier->companySupplier->ship_to_address) : [],
            'srs_branch_detail' => $supplier->companySupplier ? $supplier->companySupplier->srs_branch_detail : null,
            'updated_at' => $supplier->companySupplier ? $supplier->companySupplier->updated_at : null
        ];
    }

    public function includeSupplierBranches($supplier)
    {
        if(!($companySupplier = $supplier->companySupplier)) return;
        return $this->collection($companySupplier->supplierBranches, new SupplierBranchesTransformer);
    }

    public function includeQueueStatus($supplier)
	{
		if(!($companySupplier = $supplier->companySupplier) || (!$companySupplier->queueStatus)) return;

		return $this->item($companySupplier->queueStatus, new QueueStatusTransformer);
	}
}
