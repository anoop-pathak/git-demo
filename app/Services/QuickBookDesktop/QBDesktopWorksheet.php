<?php
namespace App\Services\QuickBookDesktop;

use Illuminate\Support\Facades\DB;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Models\Worksheet;
use Carbon\Carbon;
use QuickBooks_QBXML_Object_Estimate;
use QuickBooks_QBXML_Object_Estimate_EstimateLine;
use QBDesktopQueue;
use Exception;
use App\Services\QuickBookDesktop\BaseHandler;

class QBDesktopWorksheet extends BaseHandler
{
	public function addRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$worksheet  = Worksheet::find($ID);

			if (!$worksheet) {
				$this->task->markFailed('Worksheet not found.');
				return QUICKBOOKS_NOOP;
			}

			$job = $worksheet->job;

			if (!$job) {
				$this->task->markFailed('Job not found.');
				return QUICKBOOKS_NOOP;
			}

			if (!$job->qb_desktop_id) {

				// QBDesktopQueue::addJob($job->id, $user);
				// QBDesktopQueue::addWorksheet($worksheet, $user);

				$this->task->markFailed('Job not synced.');
				return QUICKBOOKS_NOOP;
			}

			$customer = $job->customer;

			$financialDetails = $worksheet->finacialDetail;
			$worksheetQBXML = new QuickBooks_QBXML_Object_Estimate();
			$worksheetQBXML->setCustomerListID($job->qb_desktop_id);
			$worksheetQBXML->setRefNumber(rand());
			$address = $job->address;
			$stateCode = ($state = $address->state) ? $state->code : null;
			$countryCode = ($country = $address->country) ? $country->code : null;


			$worksheetQBXML->setBillAddress(
				$address->address,
				$address->address_line_1,
				null,
				null,
				null,
				$address->city,
				$stateCode,
				null,
				$address->zip_code,
				$countryCode
			);

			$carbonDueDate = Carbon::parse($worksheet->created_at)->toDateString();
			$worksheetQBXML->setDueDate($carbonDueDate);

			foreach ($financialDetails as $detail) {
				$product = $detail->product;
				$qbdProduct = $product->qbDesktopProduct;

				$estimateLine = new QuickBooks_QBXML_Object_Estimate_EstimateLine();

				if ($worksheet->qb_desktop_id) {
					$estimateLine->set('TxnLineID', -1);
				}

				$estimateLine->setItemListID($qbdProduct->list_id);
				$estimateLine->setDescription($detail->description);
				$estimateLine->setQuantity($detail->quantity);
				$estimateLine->setUnitOfMeasure($product->unit);

				if ($worksheet->enable_selling_price) {
					$estimateLine->setRate($detail->selling_price);
				} else {
					$estimateLine->setRate($detail->unit_cost);
				}

				if (
					$worksheet->line_margin_markup
					&& (!$worksheet->margin)
					&& $detail->line_profit
				) {
					$estimateLine->setMarkupRatePercent($detail->line_profit);
				} else {
					$estimateLine->setMarkupRate(0.00);
				}

				if (
					!$worksheet->line_margin_markup
					&& $worksheet->profit
					&& !($worksheet->margin)
				) {
					$estimateLine->setMarkupRatePercent($worksheet->profit);
				} else {
					$estimateLine->setMarkupRate(0.00);
				}

				$worksheetQBXML->addEstimateLine($estimateLine);
			}

			if ($worksheet->qb_desktop_id) {
				$worksheetQBXML->setTxnID($worksheet->qb_desktop_txn_id);
				$worksheetQBXML->set('EditSequence', $worksheet->qb_desktop_sequence_number);
				$qbxml = $worksheetQBXML->asQBXML('EstimateModRq', \QuickBooks_QBXML_Object::XML_DROP);
			} else {
				$qbxml = $worksheetQBXML->asQBXML('EstimateAddRq', \QuickBooks_QBXML_Object::XML_DROP);
			}

			$xml = QBDesktopUtilities::formatForOutput($qbxml);

			if ($worksheet->qb_desktop_txn_id) {
				$xml = str_replace('EstimateLine', 'EstimateLineMod', $xml);
			}

			$xml = str_replace('<MarkupRate></MarkupRate>', '<MarkupRate>0.00</MarkupRate>', $xml);

			return $xml;

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
			return QUICKBOOKS_NOOP;
		}
	}

	public function addResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($requestID));
		$worksheet  = Worksheet::find($ID);

		DB::table('worksheets')->where('id', $ID)
			->update([
				'qb_desktop_id' => $idents['ListID'],
				'qb_desktop_txn_id' => $idents['TxnID'],
				'qb_desktop_sequence_number' => $idents['EditSequence'],
			]);

		$worksheet->qb_desktop_txn_id = $idents['TxnID'];
		$this->task->markSuccess($worksheet);
	}

	public function queryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->setTask($this->getTask($requestID));
		$worksheet = Worksheet::find($ID);

		if (!$worksheet) {
			$this->task->markFailed('Worksheet not found.');
			return QUICKBOOKS_NOOP;
		}

		if (!$worksheet->qb_desktop_txn_id) {
			$this->task->markFailed('Worksheet not synced found.');
			return QUICKBOOKS_NOOP;
		}

		if ($worksheet->qb_desktop_txn_id) {
			$tag = "<TxnID>{$worksheet->qb_desktop_txn_id}</TxnID>";
		} else {
			$this->task->markFailed('Worksheet search is not valid.');
			return QUICKBOOKS_NOOP;
		}

		$xml = '<?xml version="1.0" encoding="utf-8"?>
			<?qbxml version="2.0"?>
			<QBXML>
				<QBXMLMsgsRq onError="continueOnError">
					<EstimateQueryRq>
						' . $tag . '
					</EstimateQueryRq>
				</QBXMLMsgsRq>
			</QBXML>';

		return $xml;
	}
	public function queryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->settings->setCompanyScope($user);
		$this->setTask($this->getTask($requestID));

		$worksheet = Worksheet::find($ID);

		DB::table('worksheets')->where('id', $ID)
			->update([
				'qb_desktop_id' => $idents['ListID'],
				'qb_desktop_txn_id' => $idents['TxnID'],
				'qb_desktop_sequence_number' => $idents['EditSequence'],
			]);

		$worksheet->qb_desktop_id = $idents['ListID'];
		$this->task->markSuccess($worksheet);

		QBDesktopQueue::addWorksheet($worksheet, $user);
	}
	public function deleteRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->setTask($this->getTask($requestID));

		if (!$ID) {
			$this->task->markFailed('Id not found.');
			return QUICKBOOKS_NOOP;
		}

		if (!ine($extra, 'job_id')) {
			$this->task->markFailed('Job id found.');
			return QUICKBOOKS_NOOP;
		}

		$xml = "<TxnDelRq>
      				<TxnDelType>Estimate</TxnDelType>
      				<TxnID>".$extra['qb_desktop_txn_id']."</TxnID>
    			</TxnDelRq>";

    	$xml = QBDesktopUtilities::formatForOutput($xml);

    	return $xml;
	}
	public function deleteResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($requestID));
		$this->task->markSuccess();
	}
}