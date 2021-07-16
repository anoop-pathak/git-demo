<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidCouponException;
use App\Helpers\SecurityCheck;
use App\Models\AccountManager;
use App\Models\ApiResponse;
use App\Models\Company;
use App\Models\CompanyNote;
use App\Models\Product;
use App\Models\SetupAction;
use App\Models\Subscriber;
use App\Models\Subscription;
use App\Models\Template;
use App\Models\User;
use App\Repositories\SubscribersRepository;
use App\Services\Subscriptions\SubscriptionServices;
use App\Transformers\CompaniesTransformer;
use App\Transformers\SubscribersExportTransformer;
use App\Transformers\SubscriptionTransformer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Validator;
use Excel;
use Recurly_Error;
use Sorskod\Larasponse\Larasponse;
use App\Services\Recurly\Recurly;
use App\Services\Contexts\Context;
use App\Transformers\SubscriberStageTransformer;
use App\Models\SubscriberStageAttribute;

class SubscribersController extends ApiController
{

    /**
     * Subscribers Repo
     * @var \App\Repositories\SubscriberRepositories
     */
    protected $repo;


    /**
     * Transformer Implementation
     * @var \App\Transformer
     */
    protected $response;
    protected $subscriptionServices;

    public function __construct(Larasponse $response, SubscribersRepository $repo, SubscriptionServices $subscriptionServices, Recurly $recurly)
    {

        $this->response = $response;
        $this->subscriptionServices = $subscriptionServices;
        $this->repo = $repo;
        $this->recurlyService = $recurly;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function index()
    {
        $input = Request::all();

        $subs = $this->repo->getFilteredSubscribers($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $subscribers = $subs->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($subscribers, new CompaniesTransformer));
    }

    /**
     * add a new subscriber.
     * @access-Super Admin Only
     * POST /subscribe
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, Subscriber::getCreateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $company = $this->executeCommand('\App\Commands\SubscribeUserCommand', $input);
            $this->checkListCompletedActions($company->id, SetupAction::COMPANY_SETUP);
            return ApiResponse::json([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Subscriber']),
                'subscriber' => $this->response->item($company)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Register a new subsriber for JobProgress and JobProgress Plus.
     * To be used for public subscriber registeration
     * @ Params : Company Details, Admin Detals, Billing token to be passed as parameters
     */
    public function subscriber_signup()
    {
        $input = Request::all();
        if (!isset($input['admin_details']['same_as_company_address'])) {
            $input['admin_details']['same_as_company_address'] = 1;
        }

        if (!isset($input['billing_details']['same_as_company_address'])) {
            $input['billing_details']['same_as_company_address'] = 1;
        }

        if (!$input['admin_details']['same_as_company_address'] && !$input['billing_details']['same_as_company_address']) {
            $validator = Validator::make($input, Subscriber::validationRules(['adminDetails', 'adminAddress', 'billingDetails', 'billingAddress']));
        } elseif (!$input['billing_details']['same_as_company_address']) {
            $validator = Validator::make($input, Subscriber::validationRules(['adminDetails', 'billingDetails', 'billingAddress']));
        } else {
            $validator = Validator::make($input, Subscriber::validationRules(['adminDetails', 'billingDetails']));
        }

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        // if ($input['billing_details']['product_id'] == Product::PRODUCT_GAF_PLUS) {
        //     if (!ine($input['billing_details'], 'gaf_code')) {
        //         return ApiResponse::errorGeneral('GAF certification number required.');
        //     }

        //     if (!$this->isValidGAFCode($input['billing_details']['gaf_code'])) {
        //         return ApiResponse::errorGeneral(trans('response.error.invalid_gaf_code'));
        //     }
        // }

        try {
            $subscriber = $this->executeCommand('\App\Commands\SubscriberSignupCommand', $input);

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Subscriber']),
                'subscriber' => $this->response->item($subscriber, new CompaniesTransformer)
            ]);
        } catch (\Recurly_NotFoundError $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Recurly_ValidationError $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidCouponException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Activate subscriber
     * @access-Super Admin Only
     * POST /subscribe/activation
     *
     * @return Response
     */
    public function activation()
    {
        $input = Request::onlyLegacy('company_id');

        $validator = Validator::make($input, Subscription::getActivationRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!$this->isActivationPossible()) {
            return ApiResponse::errorGeneral(Lang::get('response.error.activation_not_possible'));
        }

        try {
            $subscription = $this->executeCommand('\App\Commands\SubscriptionCommand', $input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.company_activated'),
                'subscription' => $this->response->item($subscription, new SubscriptionTransformer)
            ]);
        } catch (\Recurly_NotFoundError $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Recurly_ValidationError $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function save_billing()
    {
        $input = Request::all();
        $validator = Validator::make($input, Subscription::getBillingRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $company = Company::findOrFail($input['company_id']);
        $input['recurly_account_code'] = $company->recurly_account_code;
        try {
            $billingDetails = $this->subscriptionServices->addBillingDetails($input);
            $this->checkListCompletedActions($input['company_id'], SetupAction::BILLING_DETAILS);
            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Billing']),
                'data' => $billingDetails
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        }
    }

    public function update_billing_info()
    {
        $input = Request::all();
        $validator = Validator::make($input, Subscription::getUpdateBillingRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $company = Company::findOrFail($input['company_id']);
        $input['recurly_account_code'] = $company->recurly_account_code;
        try {
            $billingDetails = $this->subscriptionServices->updateBillingDetails($input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Billing detail']),
                'data' => $billingDetails
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        }
    }

    public function get_billing_info()
    {

        $input = Request::onlyLegacy('company_id');

        $validator = Validator::make($input, ['company_id' => 'required']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::findOrFail($input['company_id']);

        $billing = $company->billing;

        return ApiResponse::success(['data' => $billing]);
    }

    public function workcenter()
    {
        $data = [];
        $data['subscribers']['total'] = Company::has('subscriber')->count();
        $data['subscribers']['active'] = Company::activated(Subscription::ACTIVE)->count();
        $data['subscribers']['trial'] = Company::activated(Subscription::TRIAL)->count();
        $data['subscribers']['this_month'] = Company::activationDateRange(Carbon::now()->startOfMonth(), null)->count();
        $data['account_manager']['total'] = AccountManager::count();

        // $data['subscribers']['basic']      = Company::subscribers(Product::PRODUCT_JOBPROGRESS)->count();
        $data['subscribers']['plus'] = Company::subscribers(Product::PRODUCT_JOBPROGRESS_PLUS)->count();
        $data['subscribers']['plus_free'] = Company::subscribers(Product::PRODUCT_JOBPROGRESS_PLUS_FREE)->count();
        $data['subscribers']['gaf_plus'] = Company::subscribers(Product::PRODUCT_GAF_PLUS)->count();
        // $data['subscribers']['basic_free'] = Company::subscribers(Product::PRODUCT_JOBPROGRESS_BASIC_FREE)->count();
        // $data['subscribers']['pro']        = Company::subscribers(Product::PRODUCT_JOBPROGRESS_PRO)->count();
        $data['subscribers']['standard'] = Company::subscribers(Product::PRODUCT_JOBPROGRESS_STANDARD)->count();
        $data['subscribers']['partner'] = Company::subscribers(Product::PRODUCT_JOBPROGRESS_PARTNER)->count();
        $data['subscribers']['multi'] = Company::subscribers(Product::PRODUCT_JOBPROGRESS_MULTI)->count();
        $data['subscribers']['jp25'] = Company::subscribers(Product::PRODUCT_JOBPROGRESS_25)->count();

        $usersCount = User::join('subscriptions', 'subscriptions.company_id', '=', 'users.company_id')
            ->active()
            ->where(function($query) {
                $query->loggable()
                    ->orWhere('group_id', User::GROUP_SUB_CONTRACTOR_PRIME);
            })
            ->whereIn('subscriptions.status', [Subscription::TRIAL, Subscription::ACTIVE])// only active users
            ->groupBy('subscriptions.product_id')
            ->selectRaw('COUNT(users.id) as count, product_id')
            ->notHiddenUser()
            ->get('count', 'product_id');

        $totalUsers = $usersCount->sum('count');
        $usersCount = $usersCount->pluck('count', 'product_id')->toArray();
        $data['users']['total'] = $totalUsers;
        // $data['users']['basic']      = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS) ?: 0;
        $data['users']['plus'] = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_PLUS) ?: 0;
        $data['users']['plus_free'] = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_PLUS_FREE) ?: 0;
        // $data['users']['basic_free'] = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_BASIC_FREE) ?: 0;
        // $data['users']['pro']           = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_PRO) ?: 0;
        $data['users']['gaf_plus'] = issetRetrun($usersCount, Product::PRODUCT_GAF_PLUS) ?: 0;
        $data['users']['standard'] = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_STANDARD) ?: 0;
        $data['users']['partner'] = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_PARTNER) ?: 0;
        $data['users']['multi'] = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_MULTI) ?: 0;
        $data['users']['jp25'] = issetRetrun($usersCount, Product::PRODUCT_JOBPROGRESS_25) ?: 0;

        $data['templates']['total'] = Template::system()->count();
        $data['templates']['estimate'] = Template::system()->systemEstimate()->count();
        $data['templates']['proposal'] = Template::system()->whereType(Template::PROPOSAL)->count();

        return ApiResponse::success([
            'data' => $data
        ]);
    }

    /**
     * Suspend subscriber
     * @access-Super Admin Only
     * POST /subscriber/suspend
     *
     * @return Response
     */
    public function suspend()
    {
        if (!\Auth::user()->isSuperAdmin()) {
            return ApiResponse::errorForbidden();
        }
        $input = Request::onlyLegacy('company_id', 'password', 'note');

        $validator = Validator::make($input, Subscription::getSubscriptionRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $company = Company::findOrFail($input['company_id']);
        try {
            $subscription = $company->subscription;
            $this->subscriptionServices->suspend($subscription);
            if (ine($input, 'note')) {
                $companyNote = new CompanyNote;
                $companyNote->company_id = $company->id;
                $companyNote->note = $input['note'];
                $companyNote->type = Subscription::MANUALLY_SUSPENDED;
                $companyNote->save();
            }
            return ApiResponse::success([
                'message' => Lang::get('response.success.account_suspended')
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Suspend Re-active
     * @access-Super Admin Only
     * POST /subscriber/reactivate
     *
     * @return Response
     */
    public function reactivate()
    {
        if (!\Auth::user()->isSuperAdmin()) {
            return ApiResponse::errorForbidden();
        }
        $input = Request::onlyLegacy('company_id', 'password', 'note');

        $validator = Validator::make($input, Subscription::getSubscriptionRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $company = Company::findOrFail($input['company_id']);
        try {
            $subscription = $company->subscription;
            $this->subscriptionServices->reactivate($subscription);
            if (ine($input, 'note')) {
                $companyNote = new CompanyNote;
                $companyNote->company_id = $company->id;
                $companyNote->note = $input['note'];
                $companyNote->type = Subscription::ACTIVE;
                $companyNote->save();
            }
            return ApiResponse::success([
                'message' => Lang::get('response.success.account_re_activated')
            ]);
        } catch (Recurly_Error $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Suspend Terminate
     * @access-Super Admin Only
     * POST /subscriber/terminate
     *
     * @return Response
     */
    public function terminate()
    {
        if (!\Auth::user()->isSuperAdmin()) {
            return ApiResponse::errorForbidden();
        }
        $input = Request::onlyLegacy('company_id', 'password', 'note');

        $validator = Validator::make($input, Subscription::getSubscriptionRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $company = Company::findOrFail($input['company_id']);
        try {
            $subscription = $company->subscription;
            $this->subscriptionServices->terminate($subscription);
            if (ine($input, 'note')) {
                $companyNote = new CompanyNote;
                $companyNote->company_id = $company->id;
                $companyNote->note = $input['note'];
                $companyNote->type = Subscription::TERMINATED;
                $companyNote->save();
            }
            return ApiResponse::success([
                'message' => Lang::get('response.success.account_terminate')
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Subscriber Unsubscribe
     * @access-Admin Only
     * POST /subscriber/unsubscribe
     *
     * @return Response
     */
    public function unsubscribe()
    {
        if (!\Auth::user()->isAuthority()) {
            return ApiResponse::errorForbidden();
        }
        $input = Request::onlyLegacy('company_id', 'password', 'note');
        $input['company_id'] = \Auth::user()->company_id;
        $validator = Validator::make($input, Subscription::getSubscriptionRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (!SecurityCheck::verifyPassword()) {
            return SecurityCheck::$error;
        }

        $company = Company::findOrFail($input['company_id']);
        try {
            $subscription = $company->subscription;
            $this->subscriptionServices->unsubscribe($subscription);
            if (ine($input, 'note')) {
                $companyNote = new CompanyNote;
                $companyNote->company_id = $company->id;
                $companyNote->note = $input['note'];
                $companyNote->type = Subscription::UNSUBSCRIBED;
                $companyNote->save();
            }
            return ApiResponse::success([
                'message' => Lang::get('response.success.account_unsubscribed')
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    /**
     * Validate GAF Code (certification number)
     * POST /gaf_code_verification
     *
     * @return Response
     */
    public function validateGafCode()
    {
        if (!$this->isValidGAFCode(Request::get('gaf_code'))) {
            return ApiResponse::errorGeneral(trans('response.error.invalid_gaf_code'));
        }

        return ApiResponse::success([
            'message' => trans('response.success.gaf_code_verified'),
        ]);
    }

    /**
     * Export the subscriber list
     * @return .csv file
     */
    public function export()
    {
        if (!\Auth::user()->isSuperAdmin()) {
            return ApiResponse::errorForbidden();
        }

        $input = Request::all();
        $subscriber = $this->repo->getFilteredSubscribers($input);
        $subscriber = $subscriber->with('subscriber.profile', 'subscription', 'state', 'country')->get();

        $subscriber = $this->response->collection($subscriber, new SubscribersExportTransformer);
        Excel::create('Subscribers', function ($excel) use ($subscriber) {
            $excel->sheet('sheet1', function ($sheet) use ($subscriber) {
                $sheet->fromArray($subscriber['data']);
            });
        })->export('csv');
    }

    public function updateSubscriberStage()
    {
		$input = Request::all();

		$validator = Validator::make($input, [
			'company_id' => 'required',
			'stage_attribute_id' => 'required',
		]);
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$company = Company::findOrFail($input['company_id']);
		$stage = SubscriberStageAttribute::findOrFail($input['stage_attribute_id']);

		$subscriberStage = $this->repo->saveSubscriberStage($input['company_id'], $input['stage_attribute_id']);

		return ApiResponse::success([
			'message' => Lang::get('response.success.updated',['attribute' => 'Subscriber']),
		]);
	}

	public function getSubscriberStages()
	{
		$attributes = SubscriberStageAttribute::all();

		return ApiResponse::success($this->response->collection($attributes, new SubscriberStageTransformer));
	}

    /*************************Private Section************************************/

    private function checkListCompletedActions($companyId, $action)
    {
        try {
            $company = Company::findOrFail($companyId);

            //get product id from company's Subscription Model..
            $productId = $company->subscription->product_id;

            //get action from SetupAction Model..
            $action = SetupAction::productId($productId)->ActionName($action)->first();

            //add this action in company)setup_action list..
            $company->setupActions()->attach([$action->id]);
        } catch (\Exception $e) {
            //handle exception..
        }
    }

    private function isActivationPossible()
    {
        $companyId = Request::get('company_id');
        try {
            $company = Company::findOrFail($companyId);
            $productId = $company->subscription->product_id;

            //get required actions list..
            $requiredActions = SetupAction::productId($productId)->required()->pluck('id')->toArray();
            $companyActionsList = $company->setupActions()->pluck('setup_action_id')->toArray();

            if (array_diff($requiredActions, $companyActionsList)) {
                return false;
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Check is valid GAF Code
     * @param  string $code | GAF Code
     * @return boolean
     */
    private function isValidGAFCode($code)
    {
        $prefix = substr($code, 0, 2);
        $numbers = substr($code, 2);

        if (!in_array($prefix, config('jp.gaf_code.prefix'))) {
            return false;
        }

        if ((strlen($numbers) != config('jp.gaf_code.numbers_length')) || !is_numeric($numbers)) {
            return false;
        }

        return true;
    }
}
