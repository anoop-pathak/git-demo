<?php namespace App\Services\QuickBookDesktop;

use DB;
use App\Models\QuickbookMeta;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Exception;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\Entity\Item as ItemEnity;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Traits\TaskableTrait;
use App\Models\QBDItem;


class QBDesktopDiscountItem
{
	use TaskableTrait;

	public function __construct()
	{
		$this->qbdAccount = app()->make(QBDAccount::class);
		$this->settings = app()->make(Settings::class);
		$this->qbdItem = app()->make(ItemEnity::class);
	}

 	public function addDiscountItemRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$serviceAccount = $this->qbdAccount->getServiceAccount();

			if (!$serviceAccount) {
				throw new Exception("Service Account not found.");
			}

			if (!$serviceAccount->qb_desktop_id) {
				QBDesktopQueue::addAccount($serviceAccount->id, $user);
				QBDesktopQueue::queryDiscountItem($ID, $user);
				return QUICKBOOKS_NOOP;
			}

			// $qmAccount = QuickbookMeta::where('type', QBDesktopUtilities::ACCOUNT)
			// ->whereQbDesktopUsername($user)
			// ->first();

			// if (!$qmAccount->qb_desktop_id) {
			// 	QBDesktopQueue::addAccount($qmAccount->id, $user);
			// 	QBDesktopQueue::queryDiscountItem($ID, $user);

			// 	return QUICKBOOKS_NOOP;
			// }

			$discountItem = $this->qbdItem->getDiscountItem();

			if (!$discountItem) {
				throw new Exception("Discount Item not found.");
			}

			// $qm = QuickbookMeta::whereId($ID)->whereNull('qb_desktop_id')->first();

			// if (!$qm) return QUICKBOOKS_NOOP;

			$qbxml = new \QuickBooks_QBXML_Object_DiscountItem;
			$qbxml->setName($discountItem->name);
			$qbxml->setAccountListID($serviceAccount->qb_desktop_id);
			$xml = $qbxml->asQBXML('ItemDiscountAddRq');
			$qbxml = QBDesktopUtilities::formatForOutput($xml);

			return $qbxml;

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
		}
	}

 	public function addDiscountResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$item = QBDItem::whereId($ID)->first();

			if (!$item) {
				throw new Exception("Item not found!");
			}

			$item->qb_desktop_id = $idents['ListID'];
			$item->qb_desktop_sequence_number = $idents['EditSequence'];
			$item->save();

			$this->task->markSuccess();
		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
		}

		// DB::table('quickbook_meta')->where('id', $ID)->update([
		// 	'qb_desktop_id' => $idents['ListID']
		// ]);
	}

 	public function queryDiscountItemRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$item = QBDItem::whereId($ID)->first();

			if(!$item) {
				throw new Exception("Item not found!");
			}

			// $qm = QuickbookMeta::whereId($ID)->first();

			// if (!$qm) return QUICKBOOKS_NOOP;

			$tag = "<FullName>{$item->name}</FullName>";
			if ($item->qb_desktop_id) {
				$tag = "<ListID>{$item->qb_desktop_id}</ListID>";
			}

			$xml = '<ItemDiscountQueryRq>
						' . $tag . '
					</ItemDiscountQueryRq>';

			return QBDesktopUtilities::formatForOutput($xml);

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
		}
	}
 	public function queryDiscountItemResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$item = QBDItem::whereId($ID)->first();

			if (!$item) {
				throw new Exception("Item not found!");
			}

			$item->qb_desktop_id = $idents['ListID'];
			$item->qb_desktop_sequence_number = $idents['EditSequence'];
			$item->save();

			$this->task->markSuccess();

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
		}

		// DB::table('quickbook_meta')
		// 	->where('id', $ID)
		// 	->update([
		// 		'qb_desktop_id' => $idents['ListID']
		// 	]);
	}
}