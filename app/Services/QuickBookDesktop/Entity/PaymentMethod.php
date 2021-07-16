<?php

namespace App\Services\QuickBookDesktop\Entity;

use App\Repositories\PaymentMethodsRepository;
use Illuminate\Support\Str;
use DB;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\PaymentMethod as PaymentMethodModal;

class PaymentMethod
{
    public function __construct(
        PaymentMethodsRepository $repo
    ) {
        $this->paymentMethodsRepo = $repo;
    }

    public function parse($xml)
    {
        $errnum = 0;

        $errmsg = '';

        $Parser = new QuickBooks_XML_Parser($xml);

        if ($Doc = $Parser->parse($errnum, $errmsg)) {

            $Root = $Doc->getRoot();

            $List = $Root->getChildAt('QBXML/QBXMLMsgsRs/PaymentMethodQueryRs');

            foreach ($List->children() as $item) {

                $entity = [
                    'ListID' => $item->getChildDataAt('PaymentMethodRet ListID'),
                    'TimeCreated' => $item->getChildDataAt('PaymentMethodRet TimeCreated'),
                    'TimeModified' => $item->getChildDataAt('PaymentMethodRet TimeModified'),
                    'EditSequence' => $item->getChildDataAt('PaymentMethodRet EditSequence'),
                    'Name' => $item->getChildDataAt('PaymentMethodRet Name'),
                    'IsActive' => $item->getChildDataAt('PaymentMethodRet IsActive'),
                    'PaymentMethodType' => $item->getChildDataAt('PaymentMethodRet PaymentMethodType')
                ];

                return $entity;
            }
        }

        return false;
    }

    public function create($qbPaymentMethod)
    {
        try {

            $inputMapped = $this->reverseMap($qbPaymentMethod);
            $jpPaymentMethod = $this->paymentMethodsRepo->getByLabel($qbPaymentMethod['Name']);
            if ($jpPaymentMethod) {
                return $jpPaymentMethod;
            }

            DB::beginTransaction();

            $jpPaymentMethod = $this->paymentMethodsRepo->qbdCreate($inputMapped);
            $jpPaymentMethod->qb_desktop_id = $qbPaymentMethod['ListID'];
            $jpPaymentMethod->qb_desktop_sequence_number = $qbPaymentMethod['EditSequence'];
            $jpPaymentMethod->save();

            DB::commit();

            return $jpPaymentMethod;
        } catch (Exception $e) {

            DB::rollback();
            throw $e;
        }
    }

    public function update($qbPaymentMethod)
    {
        try {

            $inputMapped = $this->reverseMap($qbPaymentMethod);

            $jpPaymentMethod = $this->paymentMethodsRepo->getByQBId($qbPaymentMethod['ListID']);
            DB::beginTransaction();

            if ($jpPaymentMethod) {

                $jpPaymentMethod = $this->paymentMethodsRepo->qbdupdate(
                    $inputMapped
                );

                DB::commit();

                return $jpPaymentMethod;
            }

            $jpPaymentMethod = $this->paymentMethodsRepo->getByLabel($qbPaymentMethod['Name']);

            if ($jpPaymentMethod) {

                DB::commit();
                return $jpPaymentMethod;
            }

            DB::commit();

            return $jpPaymentMethod;

        } catch (Exception $e) {
            DB::rollback();

            throw $e;
        }
    }

	public function reverseMap($qbPaymentMethod, $paymentMethod = null)
	{
        $method = Str::slug($qbPaymentMethod['Name'], '_');

		$mapInput = [
			'company_id' => getScopeId(),
			'label' => $qbPaymentMethod['Name'],
			'method' => $method,
			'qb_desktop_id' => $qbPaymentMethod['ListID'],
			'qb_desktop_sequence_number' => $qbPaymentMethod['EditSequence'],
			'type' => $qbPaymentMethod['PaymentMethodType'],
		];

		if($paymentMethod) {
			$mapInput['id'] = $paymentMethod->id;
		}

		return $mapInput;
    }

    public function getPaymentMethodByQbdId($qbId)
    {
        return $this->paymentMethodsRepo->getByQBDId($qbId);
    }

    public function getByName($name)
    {
        return $this->paymentMethodsRepo->getByLabel($name);
    }

    public function getPaymentMethods()
    {
        return $this->paymentMethodsRepo->getAll();
    }

    public function getByMethod($method)
    {
        return PaymentMethodModal::whereIn('company_id', [0, getScopeId()])
        ->where('method', $method)
        ->first();
    }
}
