<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Entity\BaseEntity;
use App\Services\QuickBooks\SynchEntityInterface;
use \QuickBooksOnline\API\Data\IPPAccount;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use App\Repositories\FinancialAccountRepository;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;

class Account extends BaseEntity
{
	private $accountRepo;
	public function __construct(FinancialAccountRepository $accountRepo)
	{
		parent::__construct();
		$this->accountRepo = $accountRepo;
	}
	/**
	 * Implement base class abstract function
	 *
	 * @return void
	 */
    public function getEntityName()
    {
        return 'account';
	}

	public function getJpEntity($qb_id){
		return $this->accountRepo->make()->where('quickbook_id', $qb_id)->first();
	}

	public function getJpEntityByName($name){
        $account = $this->accountRepo->make()->where('name', $name)->first();
        return $account;
	}

	/**
     * Get all Unsynched records from JP
     *
     * @return Collection
     */
    public function getUnsynchedJpEntities(){
        $accounts = $this->accountRepo->make()->where(function($query){
            $query->whereNull('quickbook_id')->orWhere('quickbook_id', '<=', 0);
        })->orderBy('level')->get();

        return $accounts;
	}

	/**
     * return the first IPPAccount by display name
     *
     * @param string $display_name
     * @return void
     */
    public function getQBOEntityByName($name){
        $account = $this->query("Select * from Account where Name = '".$name."'");

        if(!$account || count($account) == 0){
            return false;
        }
        return $account[0];
	}

	/**
	 * Create account in QBO
	 *
	 * @param SynchEntityInterface $account
	 * @return SynchEntityInterface
	 */
    public function actionCreate(SynchEntityInterface $account)
    {
		try {
			$IPPAccount = $this->getQBOEntityByName($account->name);
			if(!$IPPAccount){
				$IPPAccount = new IPPAccount();
				$this->map($IPPAccount, $account);
				$IPPAccount = $this->add($IPPAccount);
			}

            $this->linkEntity($account, $IPPAccount);
	  	} catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
    }

    /**
     * update Account in QBO
     *
     * @param SynchEntityInterface $account
     * @return void
     */
    public function actionUpdate(SynchEntityInterface $account){
        try {
            $IPPAccount = $this->get($account->getQBOId());
            $this->map($IPPAccount, $account);
            $this->update($IPPAccount);
            $this->linkEntity($account, $IPPAccount);
            return $account;
        } catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
        }
	}

	/**
	 * Import a account from QBO.
	 *
	 * @param IPPIntuitEntity $refund
	 * @return SynchEntityInterface
	 */
	public function actionImport(IPPIntuitEntity $IPPAccount)
	{
		try {
			$meta = [];
			$meta['description'] = $IPPAccount->Description;
			if($IPPAccount->ParentRef){
				$parent = $this->getJpEntity($IPPAccount->ParentRef);

				// if we can't find the entity in jp then throw exception.
				if(!$parent){
					Log::info("can't synch because of parent");
					throw new Exception("Parent Account is not synch first. Ensure to synch parent before calling this", 1);
				}

				$meta['parent_id'] = $parent->id;
			}

			if($account = $this->getJpEntity($IPPAccount->Id)){
				$account = $this->accountRepo->updateFinancialAccount(
					$account,
					$IPPAccount->Name,
					$IPPAccount->AccountType,
					$IPPAccount->AccountSubType,
					$meta
				);
			}elseif($account = $this->getJpEntityByName($IPPAccount->Name)){
				$account = $account; // Not needed just added for clarity. This will be linked instead of creation/updation
			} else{
				$meta['origin'] = 1;
				$account = $this->accountRepo->createFinancialAccount(
					$IPPAccount->Name,
					$IPPAccount->AccountType,
					$IPPAccount->AccountSubType,
					$meta
				);
			}

			$this->linkEntity($account, $IPPAccount);
			return $account;
		} catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	public function actionSynchAll(){
		$IPPAccounts = $this->getAll();
	    foreach($IPPAccounts as $IPPAccount){
			try{
				$this->actionImport($IPPAccount);
			}catch(Exception $e){
				Log::error($e);
			}
        }

        $accounts = $this->getUnsynchedJpEntities();
        foreach($accounts as $account){
			try{
				$this->actionCreate($account);
			}catch(Exception $e){
				Log::error($e);
			}
        }

    }

	/**
	 * Map Synchalbe JP entity to array representation of QBO
	 *
	 * @param SynchEntityInterface $account
	 * @return SynchEntityInterface
	 */
	private function map(IPPIntuitEntity $IPPAccount, SynchEntityInterface $account){

		$parentRef = '';
		$isSubAccount = 'false';
		$IPPAccount->Name = $account->name;
		$IPPAccount->AccountType = $account->account_type;
		$IPPAccount->AccountSubType = $account->account_sub_type;
		$IPPAccount->Description = substr($account->description , 0, 4000);
		if($this->isSubAccount($account)){
			$parentRef = $account->parent->getQBOId();
			$isSubAccount = 'true';
		}

		$IPPAccount->ParentRef = $parentRef;
		$IPPAccount->SubAccount = $isSubAccount;
		return $account;
	}

	public function isSubAccount(SynchEntityInterface $account){
		if(is_null($account->parent_id)){
			return false;
		}

		return true;
	}

	public function isParentAccountSynched(SynchEntityInterface $account){
		$parentAccount  = $account->parent;

		if(!$parentAccount) return true; // since it has no parent. consider parent synched.

		if(is_null($parentAccount->getQBOId()) or $parentAccount->getQBOId() == 0){
			return false;
		}

		return true;
	}

    /**
	 * Get QB Accounts
	 * @param $token
	 * @param $limit
	 * @param $page
	 *
	 * @return Accounts
	 *
	 */
	public function getAccounts($limit, $filters = array())
	{
		try {
			if($limit > 1000) {
				$limit = 1000;
			}

			$page  = ine($filters, 'page') ? $filters['page'] : 1;
			$products = [];
            $totaRecords = 0;
            $query = "SELECT Name, AccountType, id, SubAccount, ParentRef FROM Account WHERE Active = true";
            $name = null;

			if(ine($filters, 'Name')) {
				$name = addslashes($filters['Name']);
				$query .=  " And Name LIKE '{$name}%'";
			}

			if(ine($filters, 'Id')) {
				$query .=  " And Id = '{$filters['Id']}'";
			}

			$query .= ' ORDERBY Id DESC';

			if($limit) {
                $searchQuery =  "SELECT count(*) FROM Account WHERE Active = true";

				if(ine($filters, 'Name')) {
					$searchQuery .=  " And Name LIKE '{$name}%'";
				}

				if(ine($filters, 'Id')) {
					$searchQuery .=  " And Id = '{$filters['Id']}'";
				}

				$param = [
					'query' => $searchQuery
				];

                $queryResponse = QuickBooks::getDataByQuery($param['query']);
				$totaRecords =  (int) $queryResponse;
                $start = 1;

				if($page > 1) {
					$start += ($page - 1) * $limit;
				}

                $query .= " STARTPOSITION " . $start ." MAXRESULTS ". $limit."";
			}

			$param = [
				'query' => $query
			];

            $queryResponse = QuickBooks::getDataByQuery($param['query']);

			if(!empty($queryResponse)) {
				$products = $queryResponse;
			}

			if($limit) {
				return QuickBooks::paginatedResponse($products, $totaRecords, $limit, $page);
            }

			$data['data'] = $products;

			return $data;
		} catch (Exception $e) {
			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	/**
	 * Get Account Id
	 * @return Int Account Id
	 */
	public function getAccountId()
	{
		$param = [
			'query' => "select * from Account where FullyQualifiedName = 'Services'"
		];

		$queryResponse = QuickBooks::getDataByQuery($param['query']);

		if(!empty($queryResponse)
			&& gettype($queryResponse) == 'array'
			&& $queryResponse[0] instanceof \QuickBooksOnline\API\Data\IPPAccount) {

			return $accountId = $queryResponse[0]->Id;
		}

		$account = $this->createIncomeAccount();
		$accountId = $account->Id;

		return $accountId;
	}

	/**
	 * Create Income Account
	 * @return Account Response
	 */
	private function createIncomeAccount()
	{
		$accoountMeta = [
		  'AccountType' => 'Income',
		  'Name'        => 'Services'
		];

		try {

			$account = \QuickBooksOnline\API\Facades\Account::create($accoountMeta);

			$account = QuickBooks::getDataService()->Add($account);

			Log::info("QuickBook:Account Created.");

			return $account;
		} catch (Exception $e) {

			QuickBooks::quickBookExceptionThrow($e);
		}
	}

	public function createTask($objectId, $action, $createdSource, $origin){
        $task = QBOQueue::addTask(QuickBookTask::ACCOUNT . ' ' . $action, [
                'id' => $objectId,
            ], [
                'object_id' => $objectId,
                'object' => QuickBookTask::ACCOUNT,
                'action' => $action,
                'origin' => $origin,
                'created_source' => $createdSource
            ]);

        return $task;
    }
}