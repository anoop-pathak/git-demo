<?php
namespace App\Observers;

use QBDesktopQueue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;


class FinancialProductObserver
{
	// here is the listener
	public function subscribe( $event )
	{
		$event->listen('eloquent.creating: FinancialProduct', 'App\Observers\FinancialProductObserver@creating');
		$event->listen('eloquent.saved: FinancialProduct', 'App\Observers\FinancialProductObserver@saved');
		$event->listen('eloquent.deleted: FinancialProduct', 'App\Observers\FinancialProductObserver@deleted');
		$event->listen('eloquent.deleting: FinancialProduct', 'App\Observers\FinancialProductObserver@deleting');
	}
 	// before creating
	public function creating($financialProduct)
	{
		$financialProduct->created_by = Auth::id();
	}

	public function deleting($financialProduct)
	{
		if(Auth::check()) {
			$financialProduct->deleted_by = Auth::id();
		}

		$financialProduct->delete_trigger_action = Route::currentRouteAction();
		$financialProduct->save();
	}

	public function deleted($financialProduct)
	{
		QBDesktopQueue::deleteServiceItem($financialProduct);
		if ($financialProduct->supplier) {
			$companySupplier = $financialProduct->supplier->companySupplier;
			if ($companySupplier) {
				$companySupplier->touch();
			}
		}
	}

	public function saved($financialProduct)
	{
		// if($financialProduct->qb_desktop_id) {
		// 	QBDesktopQueue::queryProduct($financialProduct->id);
		// } else {
		// 	QBDesktopQueue::addProduct($financialProduct->id);
		// }
		if ($financialProduct->supplier) {
			$companySupplier = $financialProduct->supplier->companySupplier;
			if ($companySupplier) {
				$companySupplier->touch();
			}
		}
	}
}