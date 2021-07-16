<?php
namespace App\Http\Controllers;

use App\Transformers\FinancialAccountTransformer;
use App\Repositories\FinancialAccountRepository;
use Sorskod\Larasponse\Larasponse;
use App\Services\Contexts\Context;
use App\Exceptions\ParentAccountTypeNotSameException;
use App\Exceptions\FinancialSubAccountMaxLimitExceedException;
use App\Events\FinancialAccountDeleted;
use App\Events\FinancialAccountCreated;
use App\Events\FinancialAccountUpdated;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use Request;
use App\Models\FinancialAccount;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use QBDesktopQueue;
use App\Services\QuickBooks\Entity\Account;

class FinancialAccountController extends ApiController
{

	/**
	 * Financial Repo
	 * @var \JobProgress\Repositories\FinancialAccountRepository
	 */
	protected $repo;

	/**
	 * Display a listing of the resource.
	 * @return Response
	 */
	protected $response;

	/**
	 * Set Company Scope
	 * @return company scope
	 */
	protected $scope;

	public function __construct(Larasponse $response, FinancialAccountRepository $repo, Context $scope)
	{
		$this->repo = $repo;
		$this->scope = $scope;
		$this->response = $response;

		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	public function index()
	{
		$input = Request::all();

		$financialAccount = $this->repo->getFilteredFinancialAccount($input);

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$financialAccount = $financialAccount->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($financialAccount, new FinancialAccountTransformer));
	}

	/**
	 * Store a newly created resource in storage.
	 * POST /financial_accounts
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, FinancialAccount::getCreateRules());
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}

		try {
			$financialAccount = $this->repo->createFinancialAccount($input['name'], $input['account_type'], $input['account_sub_type'], $input);

			Event::fire('JobProgress.Events.FinancialAccountCreated', new FinancialAccountCreated($financialAccount));

			return ApiResponse::success([
				'message' => trans('response.success.created', ['attribute' => 'Financial Account']),
				'data' => $this->response->item($financialAccount, new FinancialAccountTransformer)
			]);
		} catch(FinancialSubAccountMaxLimitExceedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ParentAccountTypeNotSameException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e) {

			return ApiResponse::errorNotFound('Financial Account Type Not Found.');
		}  catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
	/**
	 * Update the specified resource in storage.
	 * PUT /customers/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$input = Request::all();

		$validator = Validator::make($input, FinancialAccount::getUpdateRules($id));
		if($validator->fails()) {

			return ApiResponse::validation($validator);
		}
		$financialAccount = $this->repo->getById($id);
		try {
			$financialAccount = $this->repo->updateFinancialAccount($financialAccount, $input['name'], $input['account_type'], $input['account_sub_type'], $input);
			Event::fire('JobProgress.Events.FinancialAccountUpdated', new FinancialAccountUpdated($financialAccount));

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Financial Account']),
				'data' => $this->response->item($financialAccount, new FinancialAccountTransformer)
			]);
		} catch(FinancialSubAccountMaxLimitExceedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ParentAccountTypeNotSameException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(ModelNotFoundException $e) {

			return ApiResponse::errorNotFound('Financial Account Type Not Found.');
		}  catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Remove the specified resource from storage.
	 * DELETE /customers/{id}
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		if(!Auth::user()->isAuthority()) {

			return ApiResponse::errorForbidden();
		}

		$financialAccount = $this->repo->getById($id);
		try {
			$financialAccount->delete();
			Event::fire('JobProgress.Events.FinancialAccountDeleted', new FinancialAccountDeleted($financialAccount));

			return ApiResponse::success([
				'message' => trans('response.success.deleted', ['attribute' => 'Financial Account']),
			]);
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function getRefundAccount()
	{
		$input = Request::all();
		$refundAccount = $this->repo->getRefundAccount();

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$refundAccount = $refundAccount->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($refundAccount, new FinancialAccountTransformer));
	}

	public function getVendorbillAccount()
	{
		$input = Request::all();
		$vendorbillAccount = $this->repo->getVendorbillAccount();

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		$vendorbillAccount = $vendorbillAccount->paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($vendorbillAccount, new FinancialAccountTransformer));
	}

	public function syncOnQBO()
	{
		try{
			set_time_limit(0);

			if($token = QBDesktopQueue::isAccountConnected()) {
				$message = 'Financial Accounts task queued successfully for desktop syncing.';
				QBDesktopQueue::allAccountsSync();
			}else{
				$message = 'Financial Accounts synced with QuickBook Online successfully.';
				$app = App::make(Account::class);
				$app->actionSynchAll();
			}

			return ApiResponse::success(['message' => $message]);
		} catch(UnauthorizedException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(QuickBookException $e){

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){

			return ApiResponse::errorGeneral($e->getMessage());
		}
	}
}
