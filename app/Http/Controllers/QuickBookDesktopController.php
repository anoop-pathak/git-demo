<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\QBDesktopUser;
use App\Services\QuickBookDesktop\QBDesktop;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\QBDesktopProductTransformer;
use App\Transformers\QBDesktopAccountTransformer;
use App\Services\FinancialProducts\FinancialProduct as ProductService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\FinancialCategory;
use App\Exceptions\QBOnlineAndDesktopNotAllowedTogetherException;

class QuickBookDesktopController extends ApiController
{

    protected $companyCamService;
    protected $jobRepo;

    public function __construct(QBDesktop $qbDesktop, Larasponse $response, ProductService $productService)
    {
        $this->qbDesktop = $qbDesktop;
        $this->response  = $response;
		$this->productService = $productService;

        parent::__construct();
    }

    /**
     * SOAP endpoint for the Web Connector to connect to
     * Post /quickbook_desktop
     * @return XML
     */
    public function webhook()
    {
        try {
            return $this->qbDesktop->connector();
        } catch (\Exception $e) {
            Log::info(getErrorDetail($e));
        }
    }

    /**
     * Download QWC File
     * Get company/qb_desktop_qwc
     * @return XMl File
     */
    public function downloadQWCFile()
    {
        try {
            if (!\Auth::user()->isAuthority()) {
                return ApiResponse::errorForbidden();
            }

            return $this->qbDesktop->downloadCompanyQWCFile(getScopeId());
        } catch (QBOnlineAndDesktopNotAllowedTogetherException $e){

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Get Password
     * Get company/qb_desktop_password
     * @return Json
     */
    public function getPassword()
    {
        try {
            if (!\Auth::user()->isAuthority()) {
                return ApiResponse::errorForbidden();
            }

            $password = $this->qbDesktop->getPassword(getScopeId());

            return ApiResponse::success(['data' => $password]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Disconnect
     * Delete company/qw_desktop_disconnect
     * @return Json
     */
    public function disconnect()
    {
        try {
            if (!\Auth::user()->isAuthority()) {
                return ApiResponse::errorForbidden();
            }

            $this->qbDesktop->accountDisconnect(getScopeId());

            return ApiResponse::success(['message' => 'Account disconnected successfully.']);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Put qbd/setup_completed
     * Mark setup as completed
     * @return response
     */
    public function markSetupAsCompleted()
    {
        if (!\Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }

        $qbUser = QBDesktopUser::where('company_id', getScopeId())->firstOrFail();

        if (!$qbUser->setup_completed) {
            return ApiResponse::errorGeneral("Please finish Web Connector setup.");
        }


        DB::table('quickbooks_user')->where('company_id', getScopeId())->update(
            ['setup_completed' => true]
        );

        return ApiResponse::success(['message' => 'Setup completed.']);
    }


	public function importProducts()
	{
		if(!Auth::user()->isAuthority()) {
			return ApiResponse::errorForbidden();
		}

        $this->qbDesktop->productImports(getScopeId());
	}

    public function importAccounts()
	{
		if(!Auth::user()->isAuthority()) {
			return ApiResponse::errorForbidden();
		}

        $this->qbDesktop->importAccounts(getScopeId());
	}

    public function createAccount()
	{
		$input = Request::onlyLegacy('category_id', 'qb_account_id', 'sync_on_qb');
		$validator = Validator::make($input, ['category_id' => 'required', 'qb_account_id' => 'required', 'sync_on_qb' => 'required']);
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$category = FinancialCategory::where('company_id', getScopeId())->findOrFail($input['category_id']);
		$this->qbDesktop->createAccount($category);
	}

    public function importUnitMeasurement()
	{
		$this->qbDesktop->importUnitMeasurement(getScopeId());
	}

    public function getQBProducts()
	{
		$input = Request::all();
		$jobs = $this->qbDesktop->getProducts($input);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		if(!$limit) {
			$jobs = $jobs->get();

			return ApiResponse::success($this->response->collection($jobs, new QBDesktopProductTransformer));
		}
		$jobs = $jobs->paginate($limit);
		return ApiResponse::success($this->response->paginatedCollection($jobs, new QBDesktopProductTransformer));
	}

    public function accountListing()
	{
		$input = Request::all();
		$accounts = $this->qbDesktop->getAccounts($input);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		if(!$limit) {
			$accounts = $accounts->get();

			return ApiResponse::success($this->response->collection($accounts, new QBDesktopAccountTransformer));
		}
		$accounts = $accounts->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($accounts, new QBDesktopAccountTransformer));
	}

    public function createProduct()
	{
		$input = Request::onlyLegacy('category_id', 'supplier_id', 'ids');
		$count = $this->qbDesktop->createProducts($input);
		$msg = 'No Record Found.';
		if($count) {
			$msg  = "{$count} records synced successfully.";
		}
		return ApiResponse::success(['message' => $msg]);
	}

    public function syncWorksheetOnQBD($id)
	{
		$this->qbDesktop->syncWorksheet($id);

		return ApiResponse::success(['message' => 'Worksheet added in queue']);
	}

    public function qbdManualSync()
	{
		$count = $this->productService->qbdManualSyncCount();
		return ApiResponse::success([
			'data' => $count
		]);
    }
}
