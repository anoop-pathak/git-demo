<?php

namespace App\Services\QuickBookDesktop;

use App\Models\QuickbookMeta;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Services\QuickBookDesktop\Entity\PaymentMethod as QBDPaymentMethod;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Traits\TaskableTrait;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Models\PaymentMethod;

class QBDesktopPaymentMethod
{
	use TaskableTrait;

	public function __construct()
	{
		$this->qbdPaymentMethod = app()->make(QBDPaymentMethod::class);
		$this->settings = app()->make(Settings::class);
	}

    public function addPaymentMethodRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {

        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$paymentMethod = PaymentMethod::find($ID);

			if (!$paymentMethod) {
				$this->task->markFailed('Payment method not found.');
				return QUICKBOOKS_NOOP;
			}

			// $qm = QuickbookMeta::where('id', $ID)
			// 	->whereQbDesktopUsername($user)
			// 	->whereNull('qb_desktop_id')
			// 	->first();
			// if(!$qm) return QUICKBOOKS_NOOP;

			$type = $paymentMethod->type;
			$method = $paymentMethod->label;

			$defaultCardTypes = [
				'Cash' => 'Cash',
				'Check' => "Check",
				'Credit Card' => "OtherCreditCard"
			];

			if (!$type) {
				if (isset($defaultCardTypes[$method])) {
					$type = $defaultCardTypes[$method];
				} else {
					$type = 'Other';
				}
			}

			$xml = '<?xml version="1.0" encoding="utf-8"?>
			<?qbxml version="8.0"?>
			<QBXML>
			    <QBXMLMsgsRq onError="stopOnError">
			        <PaymentMethodAddRq>
			            <PaymentMethodAdd>
			                <Name>' . $method . '</Name>
			                <IsActive>true</IsActive>
			                <PaymentMethodType>' . $type . '</PaymentMethodType>
			            </PaymentMethodAdd>
			        </PaymentMethodAddRq>
			    </QBXMLMsgsRq>
			</QBXML>';

			return $xml;

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
		}
    }

    public function addPaymentMethodResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$method = PaymentMethod::find($ID);

			$method->qb_desktop_id = $idents['ListID'];

			$method->qb_desktop_sequence_number = $idents['EditSequence'];

			$method->save();

			if($method->company_id == 0) {

				$qm = QuickbookMeta::firstOrNew([
					'type' => QBDesktopUtilities::PAYMENT_METHOD,
					'qb_desktop_username' => $user,
					'name' => $method->label
				]);

				$qm->qb_desktop_id = $idents['ListID'];
				$qm->save();

				$this->task->markSuccess();
			}

			// DB::table('quickbook_meta')->where('id', $ID)->update([
			// 	'qb_desktop_id' => $idents['ListID']
			// ]);

		} catch (Exception $e) {

			$this->task->markFailed((string) $e);
		}
    }

    public function queryPaymentMethodRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$method = PaymentMethod::find($ID);

			if (!$method)  {
				throw new Exception("Payment method not found.");
			}

			// $qm = QuickbookMeta::where('id', $ID)->whereQbDesktopUsername($user)->first();
			// if (!$qm) return QUICKBOOKS_NOOP;

			$xml = '<?xml version="1.0" encoding="utf-8"?>
			<?qbxml version="2.0"?>
			<QBXML>
				<QBXMLMsgsRq onError="stopOnError">
					<PaymentMethodQueryRq>
						<FullName>' . $method->label . '</FullName>
					</PaymentMethodQueryRq>
				</QBXMLMsgsRq>
			</QBXML>';

			return $xml;

		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
		}
    }

    public function queryPaymentMethodResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$method = PaymentMethod::find($ID);

			if (!$method) {
				throw new Exception("Payment method not found.");
			}

			if ($method->company_id != 0) {

				$method->qb_desktop_id = $idents['ListID'];

				$method->qb_desktop_sequence_number = $idents['EditSequence'];

				$method->save();

				$this->task->markSuccess($method);
			}

			if ($method->company_id == 0) {

				$qm = QuickbookMeta::firstOrNew([
					'type' => QBDesktopUtilities::PAYMENT_METHOD,
					'qb_desktop_username' => $user,
					'name' => $method->label
				]);

				$qm->qb_desktop_id = $idents['ListID'];
				$qm->save();

				$this->task->markSuccess();
			}
		} catch (Exception $e) {
			$this->task->markFailed((string) $e);
		}

		// $qm = QuickbookMeta::where('id', $ID)->first();
		// if(!$qm) return QUICKBOOKS_NOOP;

		// DB::table('quickbook_meta')->whereId($ID)->update([
		// 	'qb_desktop_id' => $idents['ListID']
		// ]);
    }
}
