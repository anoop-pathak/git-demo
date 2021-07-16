<?php
namespace App\Services\QuickBookDesktop;

use Illuminate\Support\Facades\DB;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Exception;
use App\Services\QuickBookDesktop\Entity\UnitOfMeasurement as QBDUnitOfMeasurement;
use App\Services\QuickBookDesktop\BaseHandler;

class QBDesktopUnitOfMeasurement extends BaseHandler
{
	public function __construct()
	{
		parent::__construct();
		$this->qbdUnitofMeasurement = app()->make(QBDUnitOfMeasurement::class);
	}

	public function addRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {
			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$uom = $this->qbdUnitofMeasurement->getJPEnity($ID);

			if (!$uom) {
				$this->task->markFailed('Unit Of Measurement not found.');
				return QUICKBOOKS_NOOP;
			}

			$QBXML = new \QuickBooks_QBXML_Object_UnitOfMeasureSet();
			$QBXML->setName($uom->name);
			$QBXML->setIsActive(true);
			$QBXML->setUnitOfMeasureType('Other');
			$QBXML->setBaseUnitName($uom->name);
			$QBXML->setBaseUnitAbbreviation($uom->name);
			$QBXML->set('DefaultUnit UnitUsedFor', 'Purchase');
			$QBXML->set('DefaultUnit Unit', $uom->name);

			$QBXML->set('DefaultUnit UnitUsedFor', 'Sales');
			$QBXML->set('DefaultUnit Unit', $uom->name);

			$xml = $QBXML->asQBXML('UnitOfMeasureSetAddRq');
			$qbxml = QBDesktopUtilities::formatForOutput($xml);

			return $qbxml;
		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
			return QUICKBOOKS_NOOP;
		}
	}
	public function addResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$uom = $this->qbdUnitofMeasurement->getJPEnity($ID);

			if (!$uom) {
				throw new Exception('Unit Of Measurement not found.');
			}

			$qbdUnitOfMeasure = $this->qbdUnitofMeasurement->parse($xml);

			$uom = $this->qbdUnitofMeasurement->update($qbdUnitOfMeasure, $uom);

			$this->task->markSuccess($uom);

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
			return QUICKBOOKS_NOOP;
		}
	}

	public function queryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$uom = $this->qbdUnitofMeasurement->getJPEnity($ID);

		if (!$uom) {
			$this->task->markFailed('UnitOfMeasurement not found.');
			return QUICKBOOKS_NOOP;
		}

		$QBXML = new \QuickBooks_QBXML_Object_UnitOfMeasureSet();

		$QBXML->set('FullName', $uom->name);

		$qbxml = $QBXML->asQBXML('UnitOfMeasureSetQueryRq');

		$xml = QBDesktopUtilities::formatForOutput($qbxml);

		return $xml;
	}
	public function queryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->settings->setCompanyScope($user);

		$this->setTask($this->getTask($requestID));

		$uom = $this->qbdUnitofMeasurement->getJPEnity($ID);

		DB::table('qbd_units_of_measurement')->where('id', $ID)->update([
			'qb_desktop_id'              => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence'],
		]);

		$uom->qb_desktop_id = $idents['ListID'];

		$this->task->markSuccess($uom);
	}
}