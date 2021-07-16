<?php

namespace App\Services\QuickBookDesktop;

use Illuminate\Support\Facades\DB;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Exception;
use App\Services\QuickBookDesktop\Entity\Account as QBDAccount;
use App\Services\QuickBookDesktop\BaseHandler;
use App\Models\FinancialAccount;

class QBDesktopAccount extends BaseHandler
{
    public function __construct()
	{
		parent::__construct();

		$this->qbdAccount = app()->make(QBDAccount::class);
	}

    public function addRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {
            $this->settings->setCompanyScope($user);

			$account = $this->qbdAccount->getJpAccount($ID);

			$task = $this->getTask($requestID);

			$this->setTask($task);

			if (!$account) {
				$task->markFailed('Account not found!');
				return QUICKBOOKS_NOOP;
			}

			if ($account->qb_desktop_delete) {
				$task->markFailed('Account deleted from QBD!');
				return QUICKBOOKS_NOOP;
			}

			$accountType = str_replace(' ', '', $account->account_type);

			$accountQBXML = new \QuickBooks_QBXML_Object_Account;
			$accountQBXML->setName($account->name);
			$accountQBXML->setDescription(substr($account->description, 0, 4095));
			$accountQBXML->set('AccountType', $accountType);

			if ($account->qb_desktop_id) {

				$accountQBXML->setListID($account->qb_desktop_id);
				$accountQBXML->setEditSequence($account->qb_desktop_sequence_number);

				$qbxml = $accountQBXML->asQBXML(QUICKBOOKS_MOD_ACCOUNT);
			} else {
				$qbxml = $accountQBXML->asQBXML(QUICKBOOKS_ADD_ACCOUNT);
			}

			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

			return $qbxml;

		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
		}
    }

    public function addResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$account = $this->qbdAccount->getJpAccount($ID);

		DB::table('financial_accounts')->where('id', $ID)->update([
			'qb_desktop_id' => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence']
		]);

		$account->qb_desktop_id = $idents['ListID'];

		$this->task->markSuccess($account);
	}

    public function queryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {

        $this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$account = $this->qbdAccount->getJpAccount($ID);

		if (!$account) {
			$this->task->markFailed('Account not found.');
			return QUICKBOOKS_NOOP;
		}

		$qbName = $account->name;

        $xml = "<AccountQueryRq>
	            <FullName>{$qbName}</FullName>
	        </AccountQueryRq>";
        $xml = QBDesktopUtilities::formatForOutput($xml);

        return $xml;
    }

    public function queryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$account = $this->qbdAccount->getJpAccount($ID);

		if (!$account) {
			$this->task->markFailed('Account not found.');
			return false;
		}

		$account->qb_desktop_id = $idents['ListID'];
		$account->qb_desktop_sequence_number = $idents['EditSequence'];
		$account->save();

		$this->task->markSuccess($account);

		return true;
    }

    public function importRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$xml = "<AccountQueryRq></AccountQueryRq>";
		$xml = QBDesktopUtilities::formatForOutput($xml);
		return $xml;
    }

    public function importResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents) 
	{
		$content = new \SimpleXMLElement($xml);
		$accounts = [];
		foreach ($content->QBXMLMsgsRs->AccountQueryRs->AccountRet as $QBXMLMsgsRs) {
			$createdAt = \Carbon\Carbon::parse($QBXMLMsgsRs->TimeCreated->__toString())->toDateTimeString();
			$updatedAt = \Carbon\Carbon::parse($QBXMLMsgsRs->TimeModified->__toString())->toDateTimeString();
			$taxLineId = $taxLineName = null;
			if($taxLineInfoRet = $QBXMLMsgsRs->TaxLineInfoRet) {
				$taxLineId   = $taxLineInfoRet->TaxLineID->__toString();
				$taxLineName = $taxLineInfoRet->TaxLineName->__toString();
			}
			$accounts[] = [
				'company_id' => 1,
				'name'    => $QBXMLMsgsRs->Name->__toString(),
				'list_id' => $QBXMLMsgsRs->ListID->__toString(),
				'is_active' => isTrue($QBXMLMsgsRs->IsActive->__toString()),
				'sub_level' => $QBXMLMsgsRs->Sublevel->__toString(),
				'account_type'   => $QBXMLMsgsRs->AccountType->__toString(),
				'account_number' => $QBXMLMsgsRs->AccountNumber->__toString(),
				'description' => $QBXMLMsgsRs->Desc->__toString(),
				'balance'     => $QBXMLMsgsRs->Balance->__toString(),
				'total_balance' => $QBXMLMsgsRs->TotalBalance->__toString(),
				'tax_line_id'   => $taxLineId,
				'tax_line_name' => $taxLineName,
				'created_at' => $createdAt,
				'updated_at' => $updatedAt,
			];
		}
		DB::table('quickbooks_account')->where('company_id', 1)->delete();
		DB::table('quickbooks_account')->insert($accounts);
    }

    public function deleteRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->setTask($this->getTask($requestID));

		$account = FinancialAccount::withTrashed()->find($ID);

		if (!$account) {
			$this->task->markFailed('Account not found.');
			return QUICKBOOKS_NOOP;
		}

		if (!$account->qb_desktop_i) {
			$this->task->markFailed('Account not found.');
			return QUICKBOOKS_NOOP;
		}

		$qbxml = new \QuickBooks_QBXML_Object_Account;
		$qbxml->setListID($account->qb_desktop_id);
		$qbxml->setEditSequence($account->qb_desktop_sequence_number);
		$qbxml->setIsActive(false);
		$qbxml = $qbxml->asQBXML(QUICKBOOKS_MOD_ACCOUNT);
		$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

		return $qbxml;
	}

	public function deleteResponse($taskId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($taskId));

		$account = FinancialAccount::withTrashed()->find($ID);

		$this->task->markSuccess($account);

		return true;
	}
}
