<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\FinancialProductImage;

class FinancialProductImageRepository extends ScopedRepository {
	/**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(FinancialProductImage $model, Context $scope){
		$this->model = $model;
		$this->scope = $scope;
	}

    public function getImages($filters)
	{
		$includeData = $this->includeData($filters);
		$productImages = $this->make($includeData);

		$this->applyFilters($productImages, $filters);

        return $productImages;
	}

    public function save($companyId, $productId, $basePath, $name, $mimeType, $size, $physicalName, $meta)
	{
		$productImage = new FinancialProductImage;
		$productImage->company_id = $companyId;
		$productImage->product_id = $productId;
		$productImage->name = $name;
		$productImage->size = $size;
		$productImage->thumb_exists = ine($meta, 'thumb_exists');
		$productImage->path = $basePath.'/'.$physicalName;
		$productImage->mime_type = $mimeType;
		$productImage->save();

        return $productImage;
    }

	/*************** Private Section *****************/
	private function applyFilters($query, $filters)
	{
		if(ine($filters, 'product_id')) {
			$query->where('product_id', $filters['product_id']);
		}
	}

    private function includeData($input)
	{
		$with = [];
		if(!isset($input['includes'])) return $with;
		$includes = (array)$input['includes'];

        return $with;
	}
}