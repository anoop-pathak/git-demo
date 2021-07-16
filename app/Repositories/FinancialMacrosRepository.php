<?php

namespace App\Repositories;

use App\Models\FinancialMacro;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class FinancialMacrosRepository extends ScopedRepository
{

    /**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(FinancialMacro $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * save details of macro
     * @param [array] [$detail] [details of sheet]
     * @param [array] [$macro] macro info]
     * @return
     */
    public function save($name, $type, $tradeId, $forAllTrades, $details = [], $meta = [])
    {
        $order = $this->getPreviousOrder();
        $macroId = ine($meta, 'macro_id') ? $meta['macro_id'] : null;

        $macro = FinancialMacro::firstOrNew([
            'macro_id' => $macroId,
            'company_id' => $this->scope->id()
        ]);

        if (!$macro->macro_id) {
            $macro->macro_id = generateUniqueToken();
        }

        $macro->macro_name = $name;
        $macro->type = $type;
        $macro->trade_id = $tradeId;
        $macro->for_all_trades = $forAllTrades;
        $macro->branch_code    = ine($meta, 'branch_code') ? $meta['branch_code'] : null;
        $macro->order          = $order;
        $macro->all_divisions_access = isset($meta['all_divisions_access']) ? $meta['all_divisions_access'] : true;
        $macro->fixed_price  = ine($meta, 'fixed_price') ? $meta['fixed_price'] : null;
        $macro->save();

        $macro->macroDetails()->delete();

        if (!empty($details)) {
            $data = [];
            foreach ($details as $detail) {
                $data[] = [
                    'product_id' => issetRetrun($detail, 'product_id'),
                    'category_id' => (int)issetRetrun($detail, 'category_id'),
                    'order' => (int)issetRetrun($detail, 'order'),
                    'quantity' => (float)issetRetrun($detail, 'quantity'),
                    'macro_link_id' => $macro->id,
                    'macro_id' => $macro->macro_id,
                    'company_id' => $macro->company_id,
                ];
            }
            DB::table('macro_details')->insert($data);
        }

        if($macro->all_divisions_access){
			$macro->divisions()->sync([]);
        }

        if(isset($meta['division_ids'])){
        	$this->assignDivisions($macro, $meta['division_ids']);
        }

        return $macro;
    }

    /**
     * get details of macro
     * @param
     * @return
     */
    public function getMacrosList($filters = [])
    {
        $query = $this->make()->Sortable();

		if(!ine($filters, 'sort_by')) {
			$query->orderBy('order','asc');
		}

        $this->applyFilters($query, $filters);

        if(isset($filters['includes'])
            && in_array('total_product_count', $filters['includes'])) {
            $query->leftJoin('macro_details', 'macro_details.macro_id', '=', 'financial_macros.macro_id');
            $query->leftJoin('financial_products', function($query){
                $query->on('financial_products.id', '=', 'macro_details.product_id');
                $query->whereNull('financial_products.deleted_at');
            });
            // check sub contractor prime
            if(Auth::user()->isSubContractorPrime()) {
                $query->where(function($query) {
                    $query->where('financial_products.labor_id', Auth::id())
                        ->orWhereNull('financial_products.labor_id');
                });
            }
            $query->select(DB::raw('financial_macros.*, COUNT(financial_products.id) as total_product'));
            $query->groupBy('financial_macros.id');
        }

        return $query;
    }

    /**
     * Get macro by id
     * @param  Int $id macro id
     * @param  array $with array
     * @return macro
     */
    public function getById($id, array $with = [])
    {
        $query = $this->make($with);
        $query->where('macro_id', $id);

        return $query->firstOrFail();
    }

    public function getMacrosByIds($macroIds, $filters = [])
    {
        $query = $this->make();
        $with = $this->includeData($filters);
        $query->with($with);
        $query->whereIn('macro_id', (array)$macroIds);

        return $query->get();
    }

    /**
     * Add Macro Divisions
     * @param  Macro $macro | Macro instance
     * @param  Array $divisions | Division Ids
     * @return Macro $macro
     */
    public function assignDivisions($macro, $divisionsIds, $forAllDivisions=false)
    {
        $macro->divisions()->sync(arry_fu($divisionsIds));
        $macro->all_divisions_access = $forAllDivisions;
        $macro->save();

        return $macro;
    }


    /*** Private Functions ***/
    /**
     * @apply filtes
     * @param [object] [$query]
     * @param [array] [$filters]
     */
    private function applyFilters($query, $filters)
    {
        $query->division();

        if (ine($filters, 'type')) {
            $query->whereIn('financial_macros.type', (array)$filters['type']);
        }

        /* Trades filter */
        if (ine($filters, 'trades')) {
            $query->trades($filters['trades']);
        }

        if(ine($filters,'branch_code')) {
            $query->where(function($query) use($filters) {
                $query->where('financial_macros.branch_code', $filters['branch_code']);
                # include other macros
                if(ine($filters, 'with_srs')) {
                    $query->orWhereNull('financial_macros.branch_code');
                }
            });
        }
    }

    /**
     * Include data
     * @param  array $input data
     * @return include data
     */
    private function includeData($input = [])
    {
        $with = [];
        $includes = isset($input['includes']) ? $input['includes'] : [];
        if (!is_array($includes) || empty($includes)) {
            return $with;
        }

        if (in_array('details', $includes)) {
            $with = [
                'details' => function($query) {
                    if($subId = \Request::get('for_sub_id')) {
                        $query->subOnly($subId);
                    }
                },
            ];
            $with[] = 'details.category';
            $with[] = 'details.supplier';
        }

        if (in_array('trade', $includes)) {
            $with[] = 'trade';
        }

        return $with;
    }


	public function getPreviousOrder()
	{
		$order = $this->make()->latest('order')->where('company_id', '=', getScopeId())->first();
		if(!$order) return 1;

		return $order->order + 1;
	}

	/**
	 * Change Order
	 * @param  Eloquent $macro       $macro
	 * @param  Int $destinationOrder integer
	 * @return Eloquent Model
	 */
	public function changeOrder($macro, $destinationOrder)
	{
		$currentOrder = $macro->order;
		if($currentOrder == $destinationOrder) {
			return $macro;
		}

		if($currentOrder < $destinationOrder) {
			$updateOrder = $this->make()->whereBetween('order', [$currentOrder, $destinationOrder]);
			$updateOrder->decrement('order');
		} else {
			$updateOrder = $this->make()->whereBetween('order', [$destinationOrder, $currentOrder]);
			$updateOrder->increment('order');
		}

		$macro = $macro->update(['order' => $destinationOrder]);

		return $macro;
	}

}
