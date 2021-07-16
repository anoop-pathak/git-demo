<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class FinancialCategoriesTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['products'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($category)
    {
        return [
            'id' => $category->id,
            'name' => $category->name,
            'default' => $category->default,
            'products_count' => $category->product_count,
            'order' => $category->order,
            'locked' => $category->locked,
            'qb_desktop_id'  =>  $category->qb_desktop_id,
            'slug' =>  $category->slug,
        ];
    }

    /**
     * Include Product
     *
     * @return League\Fractal\ItemResource
     */
    public function includeProducts($category)
    {
        $products = $category->products;
        if ($products) {
            return $this->collection($products, function ($products) {
                return $products->toArray();
            });
        }
    }
}
