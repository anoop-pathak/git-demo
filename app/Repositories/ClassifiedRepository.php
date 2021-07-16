<?php

namespace App\Repositories;

use App\Models\Classified;
use App\Services\Contexts\Context;

class ClassifiedRepository extends AbstractRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(Classified $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function saveClassified($name, $description, $trades, $otherData)
    {
        $classified = $this->model;
        $classified->name = $name;
        $classified->description = $description;
        $classified->brand = isset($otherData['brand']) ? $otherData['brand'] : null;
        $classified->link = isset($otherData['link']) ? $otherData['link'] : null;
        $classified->for_all_trades = isset($otherData['for_all_trades']) ? (bool)$otherData['for_all_trades'] : false;
        $classified->company_id = $this->scope->has() ? $this->scope->id() : null;
        $classified->save();

        if (!(bool)$classified->for_all_trades) {
            $classified->trades()->attach($trades);
        }
        return $classified;
    }

    public function getFilteredClassifieds($filters = [])
    {
        $classifieds = $this->model
            ->query()
            ->orderBy('id', 'desc');
        $this->applyFilters($classifieds, $filters);
        return $classifieds;
    }

    /***************** Private function *****************/

    private function applyFilters($query, $filters)
    {
        if(ine($filters, 'company_focus') && $this->scope->has()) {
            $query->whereCompanyId($this->scope->id());
        }else {
            $query->whereNull('company_id');
        }
        if (ine($filters, 'trades')) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('id', function ($query) use ($filters) {
                    $query->select('classified_id')->from('classified_trade')->whereIn('trade_id', $filters['trades']);
                })->orWhere('for_all_trades', true);
            });
        }

        if (ine($filters, 'title')) {
            $query->where('title', 'Like', '%' . $filters['title'] . '%');
        }
    }
}
