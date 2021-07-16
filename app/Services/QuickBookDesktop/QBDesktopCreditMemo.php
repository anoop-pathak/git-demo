<?php

namespace App\Services\QuickBookDesktop;

use App\Models\JobCredit;
use Illuminate\Support\Facades\DB;
use App\Services\QuickBookDesktop\BaseHandler;
use App\Services\QuickBookDesktop\Entity\Item as ItemEnity;
use Exception;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use App\Models\QuickBookDesktopTask;

class QBDesktopCreditMemo extends BaseHandler
{
	use CustomerAccountHandlerTrait;

	public function __construct()
	{
		parent::__construct();

		$this->qbdItem = app()->make(ItemEnity::class);
    }

    public function addcreditMemoRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$jobCredit = JobCredit::whereNull('canceled')->find($ID);

			if (!$jobCredit) {
				throw new Exception('JobCredit not found.');
			}

			if ($jobCredit->qb_desktop_delete) {
				throw new Exception('JobCredit from Quickbooks.');
			}

			$customer = $jobCredit->customer;

			if (!$customer) {
				throw new Exception('Customer not found.');
			}

			if ($customer->qb_desktop_delete) {
				throw new Exception('Customer deleted from Quickbooks.');
			}

			$job = $jobCredit->job;

			if (!$job) {
				throw new Exception('Job not found.');
			}

			if ($job->qb_desktop_delete) {
				throw new Exception('Job deleted from Quickbooks.');
			}

			$parentQBDId = null;

			if (!$job->isGhostJob()) {
				$parentQBDId = $job->qb_desktop_id;
			} else {
				$parentQBDId = $job->customer->qb_desktop_id;
			}

			if (!$parentQBDId) {
				// $this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::QUEUE_HANDLER_EVENT);
				throw new Exception('Job not synced.');
			}

			$serviceItem = $this->qbdItem->getServiceItem();

			if (!$serviceItem) {
				$serviceItem = $this->qbdItem->createServiceItem();
			}

			if (!$serviceItem->qb_desktop_id) {

				$this->task->markFailed("Service Item not synced.");

				$this->taskScheduler->addJpServiceItemTask(QuickBookDesktopTask::CREATE, $serviceItem->id, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				$this->taskScheduler->addJpCreditMemoTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				throw new Exception('Service Item not synced.');
			}

			$creditMemoQBXML = new \QuickBooks_QBXML_Object_CreditMemo();
			$creditMemoQBXML->setCustomerListID($parentQBDId);
			$creditMemoQBXML->set('RefNumber', JobCredit::QUICKBOOK_PREFIX . $jobCredit->id);
			$line = new \QuickBooks_QBXML_Object_CreditMemo_CreditMemoLine;
			$line->setDescription($jobCredit->note);
			$line->setAmount($jobCredit->amount);
			$line->setQuantity(1);
			$line->set('ItemRef ListID', $serviceItem->qb_desktop_id);
			$creditMemoQBXML->set('CreditMemoLineAdd', array($line));
			$qbxml = $creditMemoQBXML->asQBXML('CreditMemoAddRq');
			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

			return $qbxml;

		} catch (Exception $e) {
			$this->task->markFailed($e->getMessage());
			return QUICKBOOKS_NOOP;
		}
    }

    public function addcreditMemoResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$jobCredit = JobCredit::whereNull('canceled')->find($ID);

        DB::table('job_credits')->where('id', $ID)->update([
            'qb_desktop_id' => $idents['ListID'],
            'qb_desktop_sequence_number' => $idents['EditSequence'],
            'qb_desktop_txn_id' => $idents['TxnID']
        ]);

        $jobCredit->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($jobCredit);
    }

    public function creditMemoQueryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        $this->setTask($this->getTask($requestID));

		$jobCredit = JobCredit::find($ID);

		if (!$jobCredit) {
			$this->task->markFailed('JobCredit not found.');
			return QUICKBOOKS_NOOP;
		}

		if(!$jobCredit->qb_desktop_txn_id) {
			$this->task->markFailed('JobCredit not found.');
			return QUICKBOOKS_NOOP;
		}

		$xml = "<CreditMemoQueryRq>
      				<TxnID>{$jobCredit->qb_desktop_txn_id}</TxnID>
    			</CreditMemoQueryRq>";

    	$xml = QBDesktopUtilities::formatForOutput($xml);

    	return $xml;
    }

    public function creditMemoQueryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$jobCredit = JobCredit::find($ID);

		if (!$jobCredit) {
			$this->task->markFailed('JobCredit not found.');
			return false;
        }

        DB::table('job_credits')->where('id', $ID)->update([
            'qb_desktop_id' => $idents['ListID'],
            'qb_desktop_sequence_number' => $idents['EditSequence'],
            'qb_desktop_txn_id' => $idents['TxnID']
        ]);

        $queue = new \QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $user);
        $queue->enqueue(QUICKBOOKS_ADD_CREDITMEMO, $ID, QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY, null, $user);

        $jobCredit->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($jobCredit);
    }

    public function creditMemoDeleteRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        $this->setTask($this->getTask($requestId));

		$jobCredit = JobCredit::find($ID);

		if (!$jobCredit) {
			$this->task->markFailed('JobCredit not found.');
			return QUICKBOOKS_NOOP;
		}

		if (!$jobCredit->qb_desktop_txn_id) {
			$this->task->markFailed('JobCredit not synced found.');
			return QUICKBOOKS_NOOP;
		}

		$xml = "<TxnDelRq>
      				<TxnDelType>CreditMemo</TxnDelType>
      				<TxnID>{$jobCredit->qb_desktop_txn_id}</TxnID>
    			</TxnDelRq>";

    	$xml = QBDesktopUtilities::formatForOutput($xml);

    	return $xml;
    }

    public function creditMemoDeleteResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$jobCredit = JobCredit::find($ID);

		DB::table('job_credits')->where('id', $ID)->update([
			'qb_desktop_id'     => null,
			'qb_desktop_delete' => false,
			'qb_desktop_txn_id' => null,
			'qb_desktop_sequence_number' => null,
		]);

		$this->task->markSuccess($jobCredit);
    }
}
