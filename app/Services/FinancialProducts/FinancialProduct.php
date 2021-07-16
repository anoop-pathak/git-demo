<?php
namespace App\Services\FinancialProducts;;

use App\Repositories\FinancialProductsRepository;
use QBDesktopQueue;
class FinancialProduct {
	public function __construct(FinancialProductsRepository $repo)
	{
		$this->repo = $repo;
	}
	public function qbdManualSyncCount()
	{
		$count = [
			'total_qbd_queued_products' => 0,
			'total_qbd_synced_products' => 0,
		];
		if(!QBDesktopQueue::isAccountConnected()) return $count;
		$totalQBDQueueProducts = $this->repo->totalQBDQueueProducts();
		$totalSyncedProducts   = $this->repo->totalQBDSyncedProducts();
		if($totalQBDQueueProducts && ($totalQBDQueueProducts == $totalSyncedProducts)) {
			$this->repo->resetManualSyncStatus();
			return $count;
		}
		$count = [
			'total_qbd_queued_products' => $totalQBDQueueProducts,
			'total_qbd_synced_products' => $totalSyncedProducts,
		];
		return $count;
	}
}