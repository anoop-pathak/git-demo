<?php

namespace App\Services\QuickBookDesktop;

use App\Models\QuickbookMeta;
use Illuminate\Support\Facades\DB;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Models\QBDUnitOfMeasurement;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\QBDesktopProductModel;
use App\Models\FinancialAccount;
use QuickBooks_QBXML_Object_ServiceItem;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Entity\Item as ItemEnity;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Traits\TaskableTrait;
use App\Services\QuickBookDesktop\BaseHandler;
use App\Models\PaymentMethod;
use Exception;

class QBDesktopServiceItem extends BaseHandler
{
	public function __construct()
	{
		parent::__construct();
		$this->qbdAccount = app()->make(QBDAccount::class);
		$this->item = app()->make(ItemEnity::class);
	}

    public function addServiceItemRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
		try {
			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$qbxml = new QuickBooks_QBXML_Object_ServiceItem;

			if (ine($extra, 'is_financial_product')) {

				$financialProduct = FinancialProduct::where('unit', '!=', '')->find($ID);

				if (!$financialProduct) {
					throw new Exception("financialProduct not found.");
				}

				$uomModel = QBDUnitOfMeasurement::where('company_id', $financialProduct->company_id)
					->where('name', $financialProduct->unit)
					->first();

				if (!$uomModel) {
					$uomModel = new QBDUnitOfMeasurement;
					$uomModel->name = $financialProduct->unit;
					$uomModel->company_id = $financialProduct->company_id;
					$uomModel->save();
				}

				if (!$uomModel->qb_desktop_id) {

					$this->task->markFailed('');
					// QBDesktopQueue::addUnitMeasurement($uomModel, $user);

					// QBDesktopQueue::addProduct($financialProduct->id, $user);

					throw new Exception("UnitOfMeasurement not found.");
				}

				$financialCategory = $financialProduct->category;

				$account = $financialCategory->financialAccount;

				/**
				 * Old fiancial categories needs to be connected with Account
				 */
				if (!$account) {

					$account = $this->qbdAccount->getORCreateAccount($financialCategory->name);

					// If account exists and synced then map with category
					if($account && $account->qb_desktop_id) {
						$financialCategory->financial_account_id = $account->id;
						$financialCategory->save();
					}
				}

				// logx($account, 'asd');

				if (!$account->qb_desktop_id) {

					$this->task->markFailed('');

					QBDesktopQueue::addAccount($account->id, $user, ['sync_jp_account' => true]);

					QBDesktopQueue::addProduct($financialProduct->id, $user);

					throw new Exception("FinancialAccount not synced.");
				}

				$qbxml->set('UnitOfMeasureSetRef ListID', $uomModel->qb_desktop_id);
				$qbxml->setName($financialProduct->getQBDName());
				$qbxml->setIsActive(true);
				$qbxml->isSalesAndPurchase(true);

				if ($financialProduct->qb_desktop_id) {
					//selling cost
					$qbxml->set('SalesAndPurchaseMod SalesDesc', $financialProduct->description);
					$qbxml->set('SalesAndPurchaseMod SalesPrice', (float) $financialProduct->selling_price);
					$qbxml->set('SalesAndPurchaseMod IncomeAccountRef ListID', $account->qb_desktop_id);

					//purchaseing cost
					$qbxml->set('SalesAndPurchaseMod PurchaseDesc', $financialProduct->description);
					$qbxml->set('SalesAndPurchaseMod PurchaseCost', (float) $financialProduct->unit_cost);
					$qbxml->set('SalesAndPurchaseMod ExpenseAccountRef ListID', $account->qb_desktop_id);
				} else {
					//selling cost
					$qbxml->setIncomeAccountListID($account->qb_desktop_id);
					$qbxml->setSalesPrice((float) $financialProduct->selling_price);
					$qbxml->setSalesDescription($financialProduct->description);

					//purchaseing cost
					$qbxml->setPurchaseDescription($financialProduct->description);
					$qbxml->isSalesAndPurchase(true);
					$qbxml->setPurchaseCost((float) $financialProduct->unit_cost);
					$qbxml->setExpenseAccountListID($account->qb_desktop_id);
				}

				$qbxml->set('ForceUOMChange', 'true');

				if ($financialProduct->qb_desktop_id) {
					$qbDesktopProduct = $financialProduct->qbDesktopProduct;
					$qbxml->set('ListID', $financialProduct->qb_desktop_id);
					$qbxml->set('EditSequence', $qbDesktopProduct->qb_desktop_sequence_number);
					$xml = $qbxml->asQBXML('ItemServiceModRq');
				} else {
					$xml = $qbxml->asQBXML('ItemServiceAddRq');
				}

			} else {

				$this->settings->setCompanyScope($user);

				$item = $this->item->getServiceItem();

				$serviceAccountId = null;

				if (!$item) {
					throw new Exception("Service Item not found.");
				}

				$serviceAccount = $this->qbdAccount->getServiceAccount();
				if(!$serviceAccount){
					$serviceAccount = $this->qbdAccount->createServiceAccount();
				}

				if ($serviceAccount && $serviceAccount->qb_desktop_id) {
					$serviceAccountId = $serviceAccount->qb_desktop_id;
				}

				if (!$serviceAccountId) {

					$this->task->markFailed("Service Account not synced.");

					$this->taskScheduler->addJpAccountTask(QuickBookDesktopTask::QUERY, $serviceAccount->id, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					$this->taskScheduler->addJpServiceItemTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					throw new Exception('Service Account not synced.');
				}


				$qbxml->setName($item->name);
				$qbxml->setAccountListID($serviceAccountId);
				$xml = $qbxml->asQBXML('ItemServiceAddRq');
			}

			$qbxml = QBDesktopUtilities::formatForOutput($xml);

			return $qbxml;

		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
			return QUICKBOOKS_NOOP;
		}
    }

    public function addServiceItemResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			if (ine($extra, 'is_financial_product')) {

				DB::table('financial_products')->where('id', $ID)->update([
					'qb_desktop_id' => $idents['ListID'],
					'manual_qbd_sync' => true,
					'qbd_processed'   => true,
				]);

				$content = new \SimpleXMLElement($xml);

				if ($content->QBXMLMsgsRs->ItemServiceAddRs) {
					$xmlData = $content->QBXMLMsgsRs->ItemServiceAddRs->ItemServiceRet;
				} else {
					$xmlData = $content->QBXMLMsgsRs->ItemServiceModRs->ItemServiceRet;
				}

				$this->updateQuickbookProduct($extra['company_id'], $xmlData);

				$this->task->markSuccess('');

			} else {

				$item = $this->item->getServiceItem();

				if (!$item) {
					throw new Exception("Service Item not found.");
				}

				$item->qb_desktop_id = $idents['ListID'];

				$item->qb_desktop_sequence_number = $idents['EditSequence'];

				$item->save();

				$this->task->markSuccess($item);
			}

		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
			return QUICKBOOKS_NOOP;
		}
    }

    public function queryServiceItemRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			if (ine($extra, 'is_financial_product')) {
				$financialProduct = FinancialProduct::find($ID);

				if (!$financialProduct) {
					throw new Exception("FinancialProduct not found.");
				}

				$productName = $financialProduct->getQBDName();
				$qbDesktopId = $financialProduct->qb_desktop_id;

			} else {
				$item = $this->item->getServiceItem();

				if (!$item) {
					throw new Exception("Service Item not found.");
				}

				// $qm = QuickbookMeta::whereId($ID)->first();
				// if (!$qm) return QUICKBOOKS_NOOP;

				$productName = $item->name;
				$qbDesktopId = $item->qb_desktop_id;
			}


			$tag = "<FullName>{$productName}</FullName>";
			if ($qbDesktopId) {
				$tag = "<ListID>{$qbDesktopId}</ListID>";
			}

			$xml = '<ItemServiceQueryRq>
						' . $tag . '
					</ItemServiceQueryRq>';

			return QBDesktopUtilities::formatForOutput($xml);

		} catch (Exception $e) {
			$this->task->markFailed((string) $e);

			return QUICKBOOKS_NOOP;
		}
    }

    public function queryServiceItemResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			if (ine($extra, 'is_financial_product')) {

				DB::table('financial_products')->where('id', $ID)->update([
					'qb_desktop_id'  => $idents['ListID']
				]);

				$content = new \SimpleXMLElement($xml);
				$xmlData = $content->QBXMLMsgsRs->ItemServiceQueryRs->ItemServiceRet;
				$this->updateQuickbookProduct($extra['company_id'], $xmlData);

				QBDesktopQueue::addProduct($ID, $user);

				$this->task->markSuccess('');

			} else {

				// $qm = QuickbookMeta::whereId($ID)->first();
				// if (!$qm) return QUICKBOOKS_NOOP;

				// $productName = $qm->name;
				// $qbDesktopId = $qm->qb_desktop_id;
				// DB::table('quickbook_meta')
				// ->where('id', $ID)
				// ->update([
				// 	'qb_desktop_id' => $idents['ListID']
				// ]);

				$item = $this->item->getServiceItem();

				if (!$item) {
					throw new Exception("Service Item not found.");
				}

				$item->qb_desktop_id = $idents['ListID'];

				$item->qb_desktop_sequence_number = $idents['EditSequence'];

				$item->save();

				$this->task->markSuccess($item);
			}

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
			return QUICKBOOKS_NOOP;
		}
    }

    public function deleteServiceItem($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$xml = '<ListDelRq>
		      <ListDelType>ItemService</ListDelType>
		      <ListID>'.$extra['qb_desktop_id'].'</ListID>
		    </ListDelRq>';

		return QBDesktopUtilities::formatForOutput($xml);
    }

    public function deleteServiceResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->setTask($this->getTask($requestID));

		$financialProduct = FinancialProduct::where('qb_desktop_id', $extra['qb_desktop_id'])
				->where('company_id', $extra['company_id'])
				->withTrashed()
				->first();
		$financialProduct->qbDesktopProduct()->delete();
		$financialProduct->update(['qb_desktop_id' => null]);

		$this->task->markSuccess();
    }

    private function updateQuickbookProduct($companyId, $xmlData)
	{
		$unitName = $xmlData->UnitOfMeasureSetRef->FullName->__toString();
		$unitId = $xmlData->UnitOfMeasureSetRef->ListID->__toString();
		$createdAt = \Carbon\Carbon::parse($xmlData->TimeCreated->__toString())->toDateTimeString();
		$updatedAt = \Carbon\Carbon::parse($xmlData->TimeModified->__toString())->toDateTimeString();
		$qbDesktopProduct = QBDesktopProductModel::firstOrNew([
			'company_id' => $companyId,
			'list_id'    => $xmlData->ListID->__toString(),
		]);
		$qbDesktopProduct->qb_desktop_sequence_number = $xmlData->EditSequence->__toString();
		$qbDesktopProduct->name = $xmlData->Name->__toString();
		$qbDesktopProduct->uom_name = $unitName;
		$qbDesktopProduct->uom_list_id = $unitId;

		if($xmlData->SalesOrPurchase) {
			$qbDesktopProduct->sale_or_purchase_account_id = $xmlData->SalesOrPurchase->AccountRef->ListID->__toString();
			$qbDesktopProduct->sale_or_purchase_account_name = $xmlData->SalesOrPurchase->AccountRef->FullName->__toString();
			$qbDesktopProduct->sale_or_purchase_price = $xmlData->SalesOrPurchase->Price->__toString();
		} else {
			$salesAndPurchase = $xmlData->SalesAndPurchase;
			$saleIncomeAccount = $salesAndPurchase->IncomeAccountRef;
			$purchaseAccount   = $salesAndPurchase->ExpenseAccountRef;
			//selling cost
			$qbDesktopProduct->sale_description = $salesAndPurchase->SalesDesc->__toString();
			$qbDesktopProduct->sale_price = $salesAndPurchase->SalesPrice->__toString();
			$qbDesktopProduct->sale_income_account_name = $saleIncomeAccount->FullName->__toString();
			$qbDesktopProduct->sale_income_account_list_id = $saleIncomeAccount->ListID->__toString();
			//purchasing cost
			$qbDesktopProduct->purchase_description = $salesAndPurchase->PurchaseDesc->__toString();
			$qbDesktopProduct->purchase_cost = $salesAndPurchase->PurchaseCost->__toString();
			$qbDesktopProduct->purchase_expenses_account_name = $purchaseAccount->FullName->__toString();
			$qbDesktopProduct->purchase_expenses_account_list_id = $purchaseAccount->ListID->__toString();
		}
		$qbDesktopProduct->created_at = $createdAt;
		$qbDesktopProduct->updated_at = $updatedAt;
		$qbDesktopProduct->save();
	}
}
