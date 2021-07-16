<?php

namespace App\Repositories;

use App\Models\ChangeOrder;
use App\Models\ChangeOrderEntity;
use App\Models\Job;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;

class ChangeOrdersRepository extends ScopedRepository
{
    /**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(ChangeOrder $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Save Change Order
     * @param  int $jobId [description]
     * @param  array $entities [description]
     * @param  array $meta [description]
     * @return [type]           [description]
     */
    public function save(Job $job, array $entities, $meta = [])
    {
        $job->changeOrderHistory()->update(['approved' => false]);
        $changeOrder = new ChangeOrder;
        $changeOrder->company_id = $this->scope->id();
        $changeOrder->job_id = $job->id;
        $changeOrder->approved = ine($meta, 'approved') ? true : false;
        $changeOrder->order = ine($meta, 'order') ? $meta['order'] : 0;
        $changeOrder->created_by = $meta['created_by'];
        $changeOrder->invoice_updated = false;
        $changeOrder->name = ine($meta, 'name') ? $meta['name'] : 'Invoice';
        if (ine($meta, 'taxable')) {
            $changeOrder->taxable = true;
            $changeOrder->tax_rate = $meta['tax_rate'];
            $changeOrder->custom_tax_id = ine($meta, 'custom_tax_id') ? $meta['custom_tax_id'] : null;
        }
        $changeOrder->invoice_note = ine($meta, 'invoice_note') ? $meta['invoice_note'] : null;
        $changeOrder->unit_number  = ine($meta, 'unit_number') ? $meta['unit_number'] : null;
        $changeOrder->division_id  = ine($meta, 'division_id') ? $meta['division_id'] : null;
        $changeOrder->branch_code  = ine($meta, 'branch_code') ? $meta['branch_code'] : null;
        $changeOrder->ship_to_sequence_number = ine($meta, 'ship_to_sequence_number') ? $meta['ship_to_sequence_number'] : null;
        $changeOrder->save();

        //save entities
        $entities = $this->makeEntitiesObject($entities);
        $changeOrder->entities()->saveMany($entities);

        //save sum of entities in change order
        $entity = $changeOrder->entities()
            ->select(\DB::raw('sum(IF(change_order_entities.is_chargeable = 1, quantity * amount, 0)) AS total_amount'))
            ->first();
        $changeOrder->total_amount = $entity->total_amount;
        $changeOrder->save();

        return $changeOrder;
    }

    /**
     *
     * @param  ChangeOrder $changeOrder [description]
     * @param  array $entities [description]
     * @param  array $meta [description]
     * @return [type]                   [description]
     */
    public function update(ChangeOrder $changeOrder, array $entities, $meta = [])
    {
        $changeOrder->entities()->delete();

        //save entities
        $entities = $this->makeEntitiesObject($entities);
        $changeOrder->entities()->saveMany($entities);

        //get sum of entities
        $entity = $changeOrder->entities()
            ->select(DB::raw('sum(quantity * amount) AS total_amount'))
            ->first();


        $customTaxId = ine($meta, 'custom_tax_id') ? $meta['custom_tax_id'] : null;

        if (ine($meta, 'taxable')) {
            $changeOrder->taxable = true;
            $changeOrder->tax_rate = $meta['tax_rate'];
            $changeOrder->custom_tax_id = $customTaxId;
        } else {
            $changeOrder->taxable = false;
            $changeOrder->tax_rate = null;
            $changeOrder->custom_tax_id = null;
        }
        $changeOrder->invoice_updated = false;
        $changeOrder->modified_by = $meta['modified_by'];
        $changeOrder->total_amount = $entity->total_amount;
        if (isset($meta['invoice_note'])) {
            $changeOrder->invoice_note = ($meta['invoice_note']) ?: null;
        }
        if(isset($meta['name'])) {
            $changeOrder->name = ($meta['name']) ?: 'Invoice';
        }
        if(isset($meta['unit_number'])) {
            $changeOrder->unit_number = ($meta['unit_number']) ?: null;
        }
        if(isset($meta['division_id'])) {
            $changeOrder->division_id = ($meta['division_id']) ?: null;
        }
        if(isset($meta['branch_code'])) {
            $changeOrder->branch_code = ($meta['branch_code']) ?: null;
        }
        if(isset($meta['ship_to_sequence_number'])) {
            $changeOrder->ship_to_sequence_number = ($meta['ship_to_sequence_number']) ?: null;
        }
        
        $changeOrder->save();
        return $changeOrder;
    }

    /**
     * Get change order by invoice id
     * @param  Int $id Invoice Id
     * @param  array $with Array
     * @return Change Order
     */
    public function getByInvoiceId($id, array $with = [])
    {
        $query = $this->make($with);
        $query->whereInvoiceId($id);

        return $query->firstOrFail();
    }

    /**
     * Make entities object
     * @param  array $entities entities
     * @return entities
     */
    private function makeEntitiesObject($entities = [])
    {
        $orderEntities = [];

        foreach ($entities as $entity) {
            $orderEntities[] = new ChangeOrderEntity($entity);
        }

        return $orderEntities;
    }
}
