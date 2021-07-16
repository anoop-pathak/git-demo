<?php 
namespace App\Services\QuickBookDesktop;
use Illuminate\Support\Facades\DB;

class QBDesktopProduct extends \QuickBooks_Utilities
{
	public function importRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale) 
	{
		$xml = "<ItemServiceQueryRq></ItemServiceQueryRq>";
		$xml = QBDesktopUtilities::formatForOutput($xml);
		return $xml;
	}
	public function importResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents) 
	{
		$data = $this->getProducts($extra['company_id'], $user, $xml);
		if(!empty($data)) {
			DB::table('quickbooks_product')->insert($data);
		}
	}
	private function getProducts($companyId, $user, $xml)
	{
		$data = [];
		$content = new \SimpleXMLElement($xml);
		foreach ($content->QBXMLMsgsRs->ItemServiceQueryRs->ItemServiceRet as $QBXMLMsgsRs) {
			$accountId = $accountName = $price = null;
			$saleDesc = $salePrice = $saleIncomeAccountName = $saleIncomeAccountId = null;
			$purchaseDesc = $purchPrice = $purchIncomeAccountName = $purchIncomeAccountId = null;
			$parentListId = $parentName = null;
			$uom = $QBXMLMsgsRs->UnitOfMeasureSetRef;
			$uomListId = ($uom) ? $uom->ListID->__toString() : null;
			$uomFullName = ($uom) ? $uom->FullName->__toString() : null;
			if($salesOrPurchase = $QBXMLMsgsRs->SalesOrPurchase) {
				$price = $salesOrPurchase->Price->__toString();
				$accountId = $salesOrPurchase->AccountRef->ListID->__toString();
				$accountName = $salesOrPurchase->AccountRef->FullName->__toString();
			}
			if($parentRef = $QBXMLMsgsRs->ParentRef) {
				$parentListId = $parentRef->ListID->__toString();
				$parentName   = $parentRef->FullName->__toString();
			}
			if($salesAndPurchase = $QBXMLMsgsRs->SalesAndPurchase){
				$salePrice = $salesAndPurchase->SalesPrice->__toString();
				$saleIncomeAccountName = $salesAndPurchase->IncomeAccountRef->FullName->__toString();
				$saleIncomeAccountId = $salesAndPurchase->IncomeAccountRef->ListID->__toString();
				$saleDesc = $QBXMLMsgsRs->SalesAndPurchase->SalesDesc->__toString();
				$purchPrice = $salesAndPurchase->PurchaseCost->__toString();
				$purchIncomeAccountName = $salesAndPurchase->ExpenseAccountRef->FullName->__toString();
				$purchIncomeAccountId   = $salesAndPurchase->ExpenseAccountRef->ListID->__toString();
				$purchaseDesc = $salesAndPurchase->PurchaseDesc->__toString();
			}
			$saleTaxCodeName = $saleTaxCodeListId = null;
			if($saleTaxCodeRef = $QBXMLMsgsRs->SalesTaxCodeRef) {
				$saleTaxCodeName   = $QBXMLMsgsRs->SalesTaxCodeRef->FullName->__toString();
				$saleTaxCodeListId = $QBXMLMsgsRs->SalesTaxCodeRef->ListID->__toString();
			}
			$createdAt = \Carbon\Carbon::parse($QBXMLMsgsRs->TimeCreated->__toString())->toDateTimeString();
			$updatedAt = \Carbon\Carbon::parse($QBXMLMsgsRs->TimeModified->__toString())->toDateTimeString();
			$data[] = [
				'name' => $QBXMLMsgsRs->Name->__toString(),
				'company_id' => $companyId,
				'list_id' => $QBXMLMsgsRs->ListID->__toString(),
				'parent_name' => $parentName,
				'parent_list_id' => $parentListId,
				'sale_tax_code_name'    => $saleTaxCodeName,
				'sale_tax_code_list_id' => $saleTaxCodeListId,
	            'price'            => $price,                   
	            'account_list_id'  => $accountId,
	            'account_name'     => $accountName,
				'sale_description' => $saleDesc,
				'sale_price'       => $salePrice,
				'sale_income_account_name' => $saleIncomeAccountName,
				'sale_income_account_list_id' => $saleIncomeAccountId,
				'purchase_description' => $purchaseDesc,
				'purchase_cost' => $purchPrice,
				'purchase_expenses_account_name'    => $purchIncomeAccountName,
				'purchase_expenses_account_list_id' => $purchIncomeAccountId,
				'sub_lavel' => $QBXMLMsgsRs->Sublevel->__toString(),
				'uom_name'  => $uomFullName,
				'uom_list_id' => $uomListId,
				'created_at'  => $createdAt,
				'updated_at'  => $updatedAt,
			];
		}
		return $data;
	}
} 