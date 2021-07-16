<?php

namespace App\Services\QuickBookDesktop;

use App\Models\QuickbookMeta;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Models\PaymentMethod;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Entity\Item as ItemEnity;

class QBDesktopUtilities extends \QuickBooks_Utilities
{
    const QB_ADD_UNITOFMEASURESET_PRIORITY = 150;
    const QB_QUERY_ACCOUNT_PRIORITY = 100;
    const QB_ADD_ACCOUNT_PRIORITY = 95;
    const QB_ADD_SERVICE_ITEM_PRIORITY = 90;
    const QB_ADD_DISCOUNT_ITEM_PRIORITY = 89;
    const QB_QUERY_SERVICE_ITEM_PRIORITY = 93;
    const QB_QUERY_DISCOUNT_ITEM_PRIORITY = 92;
    const QB_ADD_PAYMENT_METHOD_PRIORITY = 85;
    const QB_QUERY_PAYMENT_METHOD_PRIORITY = 86;
    const QB_ADD_CUSTOMER_PRIORITY = 80;
    const QB_UPDATE_CUSTOMER_PRIORITY = 80;
    const QB_QUERY_CUSTOMER_PRIORITY = 82;
    const QB_ADD_JOB_PRIORITY = 70;
    const QB_QUERY_JOB_PRIORITY = 72;
    const QB_UPDATE_JOB_PRIORITY = 70;
    const QB_ADD_PARENT_JOB_PRIORITY = 70;
    const QB_ADD_PROJECT_PRIORITY = 65;
    const QB_ADD_INVOICE_PRIORITY = 60;
    const QB_DERIVE_INVOICE_PRIORITY = 60;
    const QB_UPDATE_INVOICE_PRIORITY = 60;
    const QB_QUERY_INVOICE_PRIORITY = 62;
    const QB_ADD_CREDITMEMO_PRIORITY = 60;
    const QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY = 50;
    const QB_QUERY_RECEIVEPAYMENT_PRIORITY = 55;
    const QUICKBOOKS_DELETE_RECEIVEPAYMENT_PRIORITY = 45;
    const QB_QUERY_CREDITMEMO_PRIORITY = 55;
    const QB_DERIVE_CREDITMEMO_PRIORITY = 45;
    const QB_QUERY_VENDOR_PRIORITY   = 70;
	const QB_ADD_VENDOR_PRIORITY    = 70;
	const QB_QUERY_BILL_PRIORITY   = 44;
    const QB_ADD_BILL_PRIORITY    = 45;
    const QB_SERVICE_PRODUCT_IMPORT_PRIORITY = 84;
	const QB_ACCOUNT_IMPORT_PRIORITY = 84;
	const QB_IMPORT_UNITOFMEASURESET_PRIORITY = 84;
	const QB_ADD_ESTIMATE_PRIORITY = 50;
	const QB_MOD_ESTIMATE_PRIORITY = 50;
	const QB_QUERY_ESTIMATE_PRIORITY = 60;
	const QB_QUERY_UNITOFMEASURESET_PRIORITY = 170;
	const QB_DELETE_SERVICE_ITEM_PRIORITY = 151;
	const QB_QUERY_DELETEDTXNS_PRIORITY = 150;
    const SERVICE_PRODUCT = 'service_product';
    const PAYMENT_METHOD = 'payment_method';
    const ACCOUNT = 'account';
    const DISCOUNT_PRODUCT = 'discount_product';
    const SALES_TAX_CODE = QuickBookDesktopTask::SALES_TAX_CODE;
	const ITEM_SALES_TAX = QuickBookDesktopTask::ITEM_SALES_TAX;
	const ITEM_SALES_TAX_GROUP = QuickBookDesktopTask::ITEM_SALES_TAX_GROUP;
	//max import count
	const QB_QUICKBOOKS_MAX_RETURNED = 10;
	const QBD_DUMP_MAX_RETURNED = 100;

    public static function createPaymentMethods($userName = null, $companyId)
    {
        setScopeId($companyId);

		$methods = PaymentMethod::Where('company_id', '0')
			->whereNull('qb_desktop_id')
			->get();

		foreach ($methods as $method) {
			$qm = QuickbookMeta::firstOrNew([
				'type' => self::PAYMENT_METHOD,
				'qb_desktop_username' => $userName,
				'name' => $method->label
			]);
			$qm->qb_desktop_id = null;
			$qm->save();
		}

		return true;
    }

    static function createDiscountItem($userName, $companyId) {
        setScopeId($companyId);

		$qbdItem = app()->make(ItemEnity::class);

		$discountItem = $qbdItem->getDiscountItem();

		if (!$discountItem) {
			$qbdItem->createDiscountItem();
		}

		// $qm = QuickbookMeta::firstOrNew([
		// 	'type' => self::DISCOUNT_PRODUCT,
		// 	'name' => 'No Charge Amount',
		// 	'qb_desktop_username' => $userName,
		// ]);

		// $qm->save();
    }

    static function createServiceProduct($userName, $companyId)
    {
        setScopeId($companyId);

		$qbdItem = app()->make(ItemEnity::class);

		$serviceItem = $qbdItem->getServiceItem();

		if (!$serviceItem) {
			$serviceItem = $qbdItem->createServiceItem();
		}

		return $serviceItem;

	// 	$qm = QuickbookMeta::create([
	// 		'type' => self::SERVICE_PRODUCT,
	// 		'name' => 'Service',
	// 		'qb_desktop_id' => null,
	// 		'qb_desktop_username' => $userName,
	// 	]);

	// 	return $qm;
    }

    static function createAccount($userName, $companyId)
    {
        setScopeId($companyId);

		$qbdAccount = app()->make(QBDAccount::class);

		$serviceAccount = $qbdAccount->getServiceAccount();

		if (!$serviceAccount) {
			$serviceAccount = $qbdAccount->createServiceAccount();
		}

		return $serviceAccount;

		// $qm = QuickbookMeta::create([
		// 	'type' => self::ACCOUNT,
		// 	'name' => 'Service Account',
		// 	'qb_desktop_id'       =>  null,
		// 	'qb_desktop_username' => $userName,
		// ]);

		// return $qm;
    }

    public static function dsn()
    {
        $config = config('database');
        $defaultConn = $config['default'];
        $dbC = $config['connections']['mysql'];

        return 'mysqli://' . urlencode($dbC['username']) . ':' . urlencode($dbC['password']) . '@' . $dbC['host'] . '/' . $dbC['database'];
    }

    public static function formatForOutput($string)
    {
        $return = '<?xml version="1.0" encoding="windows-1252"?>
			<?qbxml version="13.0"?>
			<QBXML>
				<QBXMLMsgsRq onError="continueOnError">
				' . $string . '
				</QBXMLMsgsRq>
				</QBXML>';

        return $return;
    }

    /** This function converts xml to array
	 * @param XML $xml xml to convert into array
	 * @return Array
	*/
	static public function toArray($xml)
	{
		if(!$xml) {
			return false;
		}

		try {

			$xml = simplexml_load_string($xml);

			$parser = function (\SimpleXMLElement $xml, array $collection = []) use (&$parser) {

				$nodes = $xml->children();
				$attributes = $xml->attributes();

				if (0 !== count($attributes)) {

					foreach ($attributes as $attrName => $attrValue) {
						$collection['attributes'][$attrName] = strval($attrValue);
					}
				}

				if (0 === $nodes->count()) {

					$collection['value'] = strval($xml);
					return $collection;
				}

				foreach ($nodes as $nodeName => $nodeValue) {

					if (count($nodeValue->xpath('../' . $nodeName)) < 2) {
						$collection[$nodeName] = $parser($nodeValue);
						continue;
					}

					$collection[$nodeName][] = $parser($nodeValue);
				}

				return $collection;
			};

			return [

				$xml->getName() => $parser($xml)
			];

		} catch (Exception $e) {

			return false;
		}
	}
}
