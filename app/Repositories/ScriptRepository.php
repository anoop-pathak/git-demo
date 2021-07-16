<?php

namespace App\Repositories;

use App\Models\Script;
use App\Services\Contexts\Context;

class ScriptRepository extends ScopedRepository
{

    protected $model;
    protected $scope;

    public function __construct(Script $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * Save Trade
     * @param  [string] $title       [title]
     * @param  [String] $description [description]
     * @param  [string] $type        [only customer type]
     * @return [object]              [script]
     */
    public function save($title, $description, $type, $tradeIds = null, $forAllTrades = false)
    {
        if (!$tradeIds) {
			$forAllTrades = true;
        }

        $this->model->company_id = $this->scope->id();
        $this->model->title = $title;
        $this->model->description = $description;
        $this->model->type = $type;
        $this->model->for_all_trades = $forAllTrades;
        $this->model->save();

        if(!$forAllTrades && $tradeIds) {
			$this->model->trades()->attach($tradeIds);
		}

        return $this->model;
    }


    /**
     * Update Trade
     * @param  [string] $title            [title]
     * @param  [description] $description [description]
     * @param  Script $script [object]
     * @return [scripts]                     [description]
     */
    public function update($title, $description, Script $script, $tradeIds = null, $forAllTrades = false)
    {
        if(!$tradeIds) {
			$forAllTrades = true;
        }

        $script->title = $title;
        $script->description = $description;
        $script->for_all_trades = $forAllTrades;
        $script->update();

        $script->trades()->detach();
		if(!$forAllTrades && $tradeIds) {
			$script->trades()->attach($tradeIds);
		}

        return $script;
    }

    /**
     * Get Trade Listing
     * @param  [array]  $filters  [description]
     * @param  boolean $sortable [description]
     * @return [type]            [description]
     */
    public function getFilteredScripts($filters, $sortable = true)
    {
        $scriptQueryBuilder = $this->getScripts($sortable);
        $this->applyFilters($scriptQueryBuilder, $filters);

        return $scriptQueryBuilder;
    }

    /****************** PRIVATE METHOD ********************/

    private function getScripts($sortable)
    {

        if ($sortable) {
            $script = $this->make()->Sortable();
        } else {
            $script = $this->make();
        }

        return $script;
    }

    private function applyFilters($query, $filters)
    {

        if (ine($filters, 'type')) {
            $query->where('type', '=', $filters['type']);
        }

        if (ine($filters, 'title')) {
            $query->where('title', 'Like', '%' . $filters['title'] . '%');
        }

        if(ine($filters,'trade_ids')) {
			$query->where(function($query) use($filters){
				$query->whereIn('id', function($query) use($filters){
					$query->select('script_id')->from('trade_script')->whereIn('trade_id', (array)$filters['trade_ids']);
				})->orWhere('for_all_trades', true);
			});
		}
    }
}
