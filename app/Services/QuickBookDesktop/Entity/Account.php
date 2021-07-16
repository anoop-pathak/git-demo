<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Entity\Customer as CustomerEntity;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\Repositories\FinancialAccountRepository;
use Illuminate\Support\Facades\Validator;
use App\Services\Grid\CommanderTrait;
use App\Services\QuickBookDesktop\Traits\AddressAbleTrait;
use App\Services\QuickBookDesktop\Traits\DisplayNameTrait;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\FinancialAccount;
use App\Models\FinancialAccountTypes;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use Illuminate\Support\Facades\Log;

class Account extends BaseEntity
{
	use CommanderTrait;
	use AddressAbleTrait;
	use DisplayNameTrait;

	public function __construct(
        FinancialAccountRepository $accountRepo,
		Settings $settings,
		CustomerEntity $customer
	) {
        $this->accountRepo = $accountRepo;
		$this->settings = $settings;
		$this->customerEntity = $customer;
    }

	public function getAccountByQbdId($id)
	{
		return FinancialAccount::withTrashed()->where('qb_desktop_id', $id)
			->where('company_id', '=', getScopeId())
			->first();
    }

    public function getJpAccount($id)
    {
        return FinancialAccount::withTrashed()->where('id', $id)->first();
    }

    public function getAccountType($accountType)
    {
        $type = FinancialAccountTypes::whereRaw("REPLACE(account_type,' ','') = ?",[$accountType])->first();

        return $type;
    }

    public function getAccount($qbdId, $displayName) {
        $account = FinancialAccount::withTrashed()->where('qb_desktop_id', $qbdId)
            ->where('company_id', '=', getScopeId())
            ->first();
        if($account) return $account;

        $account = FinancialAccount::withTrashed()->where('name', $displayName)
            ->where('company_id', '=', getScopeId())
            ->first();

        return $account;
    }

	public function parse($xml)
	{
        $errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

        $account = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/AccountQueryRs');

			foreach ($List->children() as $item) {

                $account = [
                    'ListID' => $item->getChildDataAt('AccountRet ListID'),
                    'EditSequence' => $item->getChildDataAt('AccountRet EditSequence'),
                    'TimeCreated' =>  $item->getChildDataAt('AccountRet TimeCreated'),
                    'TimeModified' =>  $item->getChildDataAt('AccountRet TimeModified'),
                    'Name' =>  $item->getChildDataAt('AccountRet Name'),
                    'FullName' =>  $item->getChildDataAt('AccountRet FullName'),
                    'IsActive' => $item->getChildDataAt('AccountRet IsActive'),
                    'Sublevel' =>  $item->getChildDataAt('AccountRet Sublevel'),
                    'AccountType' => $item->getChildDataAt('AccountRet AccountType'),
                    'Desc' => $item->getChildDataAt('AccountRet Desc'),
                    'ParentRef' => [
                        'ListID' => $item->getChildDataAt('AccountRet ParentRef ListID'),
                        'FullName' => $item->getChildDataAt('AccountRet ParentRef FullName'),
                    ],
                ];
			}
		}

		return $account;
	}

    public function syncParse($xml)
    {
        $errnum = 0;

        $errmsg = '';

        $Parser = new QuickBooks_XML_Parser($xml);

        $account = [];

        if ($Doc = $Parser->parse($errnum, $errmsg)) {

            $Root = $Doc->getRoot();

            $List = $Root->getChildAt('QBXML/QBXMLMsgsRs/AccountQueryRs');

            foreach ($List->children() as $item) {

                $account[] = [
                    'ListID' => $item->getChildDataAt('AccountRet ListID'),
                    'EditSequence' => $item->getChildDataAt('AccountRet EditSequence'),
                    'TimeCreated' =>  $item->getChildDataAt('AccountRet TimeCreated'),
                    'TimeModified' =>  $item->getChildDataAt('AccountRet TimeModified'),
                    'Name' =>  $item->getChildDataAt('AccountRet Name'),
                    'FullName' =>  $item->getChildDataAt('AccountRet FullName'),
                    'IsActive' => $item->getChildDataAt('AccountRet IsActive'),
                    'Sublevel' =>  $item->getChildDataAt('AccountRet Sublevel'),
                    'AccountType' => $item->getChildDataAt('AccountRet AccountType'),
                    'Desc' => $item->getChildDataAt('AccountRet Desc'),
                    'ParentRef' => [
                        'ListID' => $item->getChildDataAt('AccountRet ParentRef ListID'),
                        'FullName' => $item->getChildDataAt('AccountRet ParentRef FullName'),
                    ],
                ];
            }
        }

        return $account;
    }

	function create($qbdAccount)
	{
        $mappedInput = $this->reverseMap($qbdAccount);

        $accountType = $this->getAccountType($mappedInput['account_type']);

        if(!$accountType) {
            throw new Exception('Account type not found!');
        }

        if(ine($mappedInput, 'parent_ref')) {
            $parentAccount = $this->getAccountByQbdId($mappedInput['parent_ref']);

            if(!$parentAccount) {
                throw new Exception('Parent Account not found!');
            }

            $mappedInput['parent_id'] = $parentAccount->id;
        }

        $account = $this->accountRepo->createFinancialAccount(
            $mappedInput['full_name'],
            $accountType->account_type,
            $accountType->account_sub_type,
            $mappedInput
        );

        $this->linkEntity($account, $qbdAccount, $attachOrigin = true);

        return $account;
	}

	function update($qbdAccount,  FinancialAccount $account)
	{
        $mappedInput = $this->reverseMap($qbdAccount, $account);

        if($mappedInput['active'] == 'false'){
            $this->linkEntity($account, $qbdAccount);
            $account->delete();
            return $account;
        }

        $accountType = $this->getAccountType($mappedInput['account_type']);

        if (!$accountType) {
            throw new Exception('Account type not found!');
        }

        if (ine($mappedInput, 'parent_ref')) {

            $parentAccount = $this->getAccountByQbdId($mappedInput['parent_ref']);

            if (!$parentAccount) {
                throw new Exception('Parent Account not found!');
            }

            $mappedInput['parent_id'] = $parentAccount->id;
        }

        $account = $this->accountRepo->updateFinancialAccount(
            $account,
            $mappedInput['full_name'],
            $accountType->account_type,
            $accountType->account_sub_type,
            $mappedInput
        );

        $this->linkEntity($account, $qbdAccount);

        return $account;
	}

	public function reverseMap($input, FinancialAccount $account = null)
	{
		$mapInput = [
            'qb_desktop_id' => $input['ListID'],
            'qb_desktop_sequence_number' => $input['EditSequence'],
            'created_at' => $input['EditSequence'],
            'object_last_updated' => $input['EditSequence'],
            'name' =>  $input['Name'],
            'full_name' => $input['FullName'],
            'active' => $input['IsActive'],
            'level' =>  $input['Sublevel'],
            'account_type' => $input['AccountType'],
            'description' => ($input['Desc']) ? $input['Desc']: ''
        ];

        if($input['ParentRef']) {
            $mapInput['parent_ref'] = $input['ParentRef']['ListID'];
        }

		if($account) {
			$mapInput['id'] = $account->id;
		}

		return $mapInput;
    }

    public function getServiceAccount($companyId = null)
    {
        if(!$companyId) {
            $companyId = getScopeId();
        }

        return FinancialAccount::withTrashed()->where('name', 'Service Account')
            ->where('company_id', $companyId)
            ->first();
    }

    public function createServiceAccount()
    {
        $accountType = $this->getAccountType('Income');

        $account = $this->accountRepo->createFinancialAccount(
            'Service Account',
            $accountType->account_type,
            $accountType->account_sub_type
        );

        return $account;
    }

    public function getOrCreateAccount($name)
    {

        $account = FinancialAccount::where('company_id', getScopeId())
            ->where('name', $name)
            ->first();

        if($account) {
            return $account;
        }

        $accountType = FinancialAccountTypes::where('account_type', 'Income')
            ->where('account_sub_type', 'ServiceFeeIncome')
            ->first();

        $input = [
            'name' => $name,
            'account_type' => $accountType->account_type,
            'account_sub_type' => $accountType->account_sub_type,
        ];

        $validator = Validator::make($input, FinancialAccount::getCreateRules());

        if ($validator->fails()) {
            throw new Exception('Account validation failed.');
        }

        $account = $this->accountRepo->createFinancialAccount($input['name'], $input['account_type'], $input['account_sub_type'], $input);

        return $account;
    }

    public function createOrUpdate($qbdAccount)
    {
        try{
            $account = $this->getAccount($qbdAccount['ListID'], $qbdAccount['FullName']);

            if(!$account) {
                $this->create($qbdAccount);
            } else {
                $this->update($qbdAccount, $account);
            }
        } catch(Exception $e){
            Log::info($e->getMessage());
        }

    }
}