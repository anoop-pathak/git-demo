<?php

namespace App\Repositories;

use App\Models\ProductsFocus;

class ProductsFocusRepository extends AbstractRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

    function __construct(ProductsFocus $model)
    {

        $this->model = $model;
    }

    public function saveProduct($name, $description, $trades, $otherData)
    {
        $product = $this->model;
        $product->name = $name;
        $product->description = $description;
        $product->brand = isset($otherData['brand']) ? $otherData['brand'] : null;
        $product->link = isset($otherData['link']) ? $otherData['link'] : null;
        $product->for_all_trades = isset($otherData['for_all_trades']) ? (bool)$otherData['for_all_trades'] : false;
        $product->save();

        if (!(bool)$product->for_all_trades) {
            $product->trades()->attach($trades);
        }
        return $product;
    }

    public function getFilteredProducts($filters = [])
    {
        $products = $this->model
            ->query()
            ->orderBy('id', 'desc');
        $this->applyFilters($products, $filters);
        return $products;
    }

    /***************** Private function *****************/

    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'trades')) {
            $query->where(function ($query) use ($filters) {
                $query->whereIn('id', function ($query) use ($filters) {
                    $query->select('products_focus_id')->from('products_focus_trades')->whereIn('trade_id', $filters['trades']);
                })->orWhere('for_all_trades', true);
            });
        }

        if (ine($filters, 'title')) {
            $query->where('title', 'Like', '%' . $filters['title'] . '%');
        }
    }
}
