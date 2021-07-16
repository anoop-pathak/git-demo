<?php
namespace App\Repositories;

use App\Models\CustomTax;
use App\Services\Contexts\Context;
use QBDesktopQueue;

class CustomTaxRepository extends ScopedRepository
{

	public function __construct(Context $scope, CustomTax $model)
	{
        $this->scope = $scope;
        $this->model = $model;
	}

	public function getQuickBookTaxes()
	{
		$taxes = CustomTax::whereCompanyId(getScopeId())
			->where('quickbook_tax_code_id', '>', 0)
            ->get()->toArray();
            
        return $taxes;
    }
    
    public function isValidQuickBookTax($customTaxId)
	{
        $tax = CustomTax::whereCompanyId(getScopeId())
            ->where('id', $customTaxId)
			->where('quickbook_tax_code_id', '>', 0)
            ->first();
        
        if($tax) {
           return true;
        }

        return false;
    }

    public function getListing($filters)
    {
        $taxTypes = '';

        $isQBD = false;

        $userName = QBDesktopQueue::getUsername($this->scope->id());

        if ($userName) {
            $isQBD = QBDesktopQueue::isAccountConnected($userName);
        }

        if (ine($filters, 'type')) {
            $taxTypes = $filters['type'];
        }

        $qbTaxCount = 0;

        if ($taxTypes != 'jp') {
            $taxes = $this->make()->where('quickbook_tax_code_id', '>', 0);
            $qbTaxCount = $taxes->count();
        }

        if ($isQBD && $taxTypes != 'jp') {
            $taxes = $this->make()->whereNotNull('qb_desktop_id');
             $qbTaxCount = $taxes->count();
        }

        if (!$qbTaxCount || $taxTypes == 'jp') {
            $taxes = $this->make()
                ->whereNull('qb_desktop_id')
                ->whereNull('quickbook_tax_code_id');
        }

        $this->applyFilters($taxes, $filters);

        return $taxes;
    }

    /************* Private Section ***************/

    private function applyFilters($query, $filters = array())
    {
        // search by title
        if(ine($filters, 'title')) {
            $query->where('title','like','%'.$filters['title'].'%');
        }
    }
}