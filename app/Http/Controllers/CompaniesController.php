<?php

namespace App\Http\Controllers;

use Request;
use Exception;
use FlySystem;
use Carbon\Carbon;
use App\Models\State;
use App\Models\Company;
use App\Models\EVClient;
use App\Models\Supplier;
use App\Models\QuickBook;
use App\Models\ApiResponse;
use App\Models\CompanyNote;
use App\Models\HoverClient;
use App\Models\SetupAction;
use App\Models\CompanyState;
use App\Models\QBDesktopUser;
use App\Models\CompanyCamClient;
use App\Services\Contexts\Context;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\Lang;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Validator;
use App\Exceptions\MobileMessageException;
use App\Transformers\CompaniesTransformer;
use App\Transformers\QuickbookTransformer;
use App\Transformers\HoverClientTransformer;
use App\Transformers\QuickbookPayTransformer;
use App\Transformers\CompanyStatesTransformer;
use App\Transformers\EagleViewClientTransformer;
use App\Services\MobileMessages\MobileMessageService;
use App\Transformers\WorksheetTemplatesTransformer;
use App\Services\Recurly\Recurly;
use App\Services\Companies\CompaniesService;
use Illuminate\Support\Facades\Auth;
use App\Models\CompanyNetwork;
use App\Models\NetworkMeta;

class CompaniesController extends ApiController
{

    /**
     * Display a listing of the resource.
     * GET /companies
     *
     * @return Response
     */
    protected $response;
    protected $company;
    protected $scope;
    protected $recurlyService;
    protected $service;

    public function __construct(Larasponse $response, Company $company, Context $scope, Recurly $recurlyService, CompaniesService $service)
    {
        $this->company = $company;
        $this->response = $response;
        $this->scope = $scope;
        $this->recurlyService = $recurlyService;
        $this->service = $service;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
        $this->middleware('company_scope.ensure', ['only' => ['show', 'update', 'add_notes', 'upload_logo']]);
    }

    public function index()
    {
        $repId = Request::get('account_manager_id') ? Request::get('account_manager_id') : null;

        $limit = Request::get('limit') ? Request::get('limit') : 3;

        $companies = $repId ? Company::where('account_manager_id', $repId)->paginate($limit) : Company::paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($companies, new CompaniesTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /companies
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, Company::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if (($company = Company::create($input))) {
            foreach (Request::get('states') as $stateId) {
                $state = State::find($stateId);
                $company->states()->save($state);
            }

            return ApiResponse::success([
                'message' => Lang::get('response.success.saved', ['attribute' => 'Company'])
            ]);
        } else {
            return ApiResponse::errorInternal();
        }
    }

    /**
     * Display the specified resource.
     * GET /companies/show
     *
     * @headerParam company-scope
     * @return Response
     */
    public function show()
    {
        $id = $this->scope->id();
        $company = $this->company->findOrFail($id);
        return ApiResponse::success(['data' => $this->response->item($company, new CompaniesTransformer)]);
    }

    /**
     * Update the specified resource in storage.
     * PUT /companies/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update()
    {
        $id = $this->scope->id();
        $company = Company::findOrFail($id);
        $input = Request::all();
        $validator = Validator::make($input, Company::getUpdateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $input['id'] = $id;
        try {
            $company = $this->executeCommand('\App\Commands\SubscriberUpdateCommand', $input);
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Company'])
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function add_notes()
    {

        $company_id = $this->scope->id();
        $input = Request::onlyLegacy('notes');

        $validator = Validator::make($input, Company::getAddNotesRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        /** @noinspection PhpUndefinedClassInspection */
        $companyNote = new CompanyNote;
        $companyNote->company_id = $company_id;
        $companyNote->note = $input['notes'];

        if ($companyNote->save()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.added', ['attribute' => 'Note']),
                'data' => $companyNote
            ]);
        }

        return ApiResponse::errorInternal();
    }

    public function notes()
    {
        $company_id = $this->scope->id();
        $company = Company::findOrFail($company_id);
        $notes = $company->notes;
        return ApiResponse::success(['data' => $notes]);
    }

    public function upload_logo()
    {
        $id = $this->scope->id();
        $input = Request::onlyLegacy('logo');

        $validator = Validator::make($input, Company::getUploadLogoRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::findOrFail($id);

        //delete existing logo..
        // if(!empty($company->logo)) {
        // 	FlySystem::delete(config('jp.BASE_PATH').$company->logo);
        // }

        $filename = $id . '_' . Carbon::now()->timestamp . '.jpg';
        $baseName = 'company/logos/' . $filename;
        $basePath = config('jp.BASE_PATH');

        $image = \Image::make($input['logo']);
        if ($image->height() > $image->width()) {
            $image->heighten(200, function ($constraint) {
                $constraint->upsize();
            });
        } else {
            $image->widen(200, function ($constraint) {
                $constraint->upsize();
            });
        }

        FlySystem::uploadPublicaly($basePath . $baseName, $image->encode()->getEncoded());

        $company->logo = $baseName;

        if ($company->save()) {
            return ApiResponse::success([
                'message' => Lang::get('response.success.updated', ['attribute' => 'Company logo']),
                'data' => [
                    'logo' => FlySystem::publicUrl($basePath . $baseName),
                ]
            ]);
        }

        return ApiResponse::errorInternal();
    }

    public function save_states()
    {
        $input = Request::onlyLegacy('company_id', 'states', 'company_country');
        $validator = Validator::make($input, Company::getSaveCompanyStatesRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = Company::find($input['company_id']);
        if (!$company) {
            return ApiResponse::errorNotFound(Lang::get('response.error.not_found', ['attribute' => 'Company']));
        }
        try {
            $company->company_country = $input['company_country'];
            $company->save();
            $statesIds = $company->states->pluck('id')->toArray();

            $oldStateIds = array_diff($statesIds, (array)$input['states']);
            $newStateIds = array_filter(array_diff((array)$input['states'], $statesIds));

            if (!empty($oldStateIds)) {
                $company->states()->detach($oldStateIds);
            }

            if ($newStateIds) {
                $company->states()->attach($newStateIds);
            }

            $this->checkListCompletedActions($company->id, SetupAction::STATES);

            return ApiResponse::success(['message' => Lang::get('response.success.saved', ['attribute' => 'Company states'])]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'), $e);
        }
    }

    public function get_states()
    {
        $company = Company::with('states', 'states.tax')->find($this->scope->id());
        if (!$company) {
            return ApiResponse::errorNotFound(Lang::get('response.error.not_found', ['attribute' => 'Company']));
        }

        $states = $company->states;

        $data = $this->response->collection($states, new CompanyStatesTransformer);

        $data['current_state_id'] = $company->office_state;

        return ApiResponse::success($data);
    }

    public function get_setup_actions($companyId)
    {
        $company = Company::findOrFail($companyId);

        try {
            //company's completed action list..
            $companySetupActions = $company->setupActions()->pluck('setup_action_id')->toArray();

            //find actions list by company's selected product id..
            $productId = $company->subscription->product_id;
            $actions = SetupAction::productId($productId);
            $actionsList = $actions->get()->toArray();

            //show the completed actions in list..
            foreach ($actionsList as $key => $action) {
                $actionsList[$key]['completed'] = false;
                if (in_array($action['id'], $companySetupActions)) {
                    $actionsList[$key]['completed'] = true;
                }
            }

            //get only required actions list..
            $requiredActions = $actions->required()->pluck('id')->toArray();

            //add information that activation is possible or not..
            $data = [
                'activation_possible' => true,
                'actions_list' => $actionsList
            ];
            if (array_diff($requiredActions, $companySetupActions)) {
                $data['activation_possible'] = false;
            }

            // return actions list..
            return ApiResponse::success([
                'data' => $data
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(Lang::get('response.error.internal'));
        }
    }

    /**
     * Save Tax Rate of state.
     * Post /company/states/tax
     * @return Response
     */
    public function saveStateTax()
    {
        $input = Request::onlyLegacy('state_id', 'tax_rate', 'material_tax_rate', 'labor_tax_rate');

        $validator = Validator::make($input, CompanyState::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $companyState = CompanyState::whereCompanyId($this->scope->id())
            ->whereStateId($input['state_id'])
            ->with('state')
            ->firstOrFail();
        try {
            $taxRate = $input['tax_rate'];
            $materialTaxRate = $input['material_tax_rate'];
            $laborTaxRate = $input['labor_tax_rate'];

            if ($taxRate == '') {
                $taxRate = null;
            }

            if ($materialTaxRate == '') {
                $materialTaxRate = null;
            }

            if ($laborTaxRate == '') {
                $laborTaxRate = null;
            }

            $companyState->material_tax_rate = $materialTaxRate;
            $companyState->labor_tax_rate = $laborTaxRate;
            $companyState->tax_rate = $taxRate;
            $companyState->update();

            $state = $companyState->state;

            $data = [
                'id' => $state->id,
                'name' => $state->name,
                'code' => $state->code,
                'country_id' => $state->country_id,
                'tax_rate' => $taxRate,
                'material_tax_rate' => $companyState->material_tax_rate,
                'labor_tax_rate' => $companyState->labor_tax_rate,
            ];

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'State tax']),
                'state' => $data
            ]);
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Share App urls
     * @return response
     */
    public function shareAppUrls()
    {
        $company = Company::findOrFail($this->scope->id());
        try {
            $countryCode = $company->country->code;
            $phoneNumber = $company->office_phone;
            $message = new  MobileMessageService;
            $message->send($phoneNumber, config('mobile-message.contents'), $countryCode);

            return ApiResponse::success([
                'message' => 'Mobile App urls shared successfully.',
            ]);
        } catch (MobileMessageException $e) {
            return ApiResponse::errorGeneral($e->getMessage());
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * attach templates with worksheets
     *
     * POST - /company/attach_worksheet_templates
     *
     * @return response
     */
    public function attachWorksheetTemplates()
    {
        $input = Request::onlyLegacy('template_ids');

        $validator = Validator::make($input, ['template_ids' => 'array']);

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $company = $this->company->findOrFail($this->scope->id());

        try {
            $templateIds = arry_fu((array)$input['template_ids']);
            $company->templates()->detach();

            if (!empty($templateIds)) {
                $company->templates()->attach($templateIds);
            }
        } catch (Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success(['message' => trans('response.success.saved', ['attribute' => 'Worksheet templates'])]);
    }

    /**
     * list worksheet templates
     *
     * GET - /company/worksheet_templates
     *
     * @return response
     */
    public function listWorksheetTemplates()
    {
        $company = $this->company->findOrFail($this->scope->id());
        $with = [];
        $includes = Request::onlyLegacy('includes');

        if(is_array($includes) && in_array('pages', $includes)) {
            $with[] = 'pages';
        }

        $templates = $company->templates()->with($with)->get();
        $data = $this->response->collection($templates, new WorksheetTemplatesTransformer);

        return ApiResponse::success($data);
    }

    /**
     * Connected third parties
     * @return data
     */
    public function connectedThirdParties()
    {
        $input = Request::onlyLegacy('name');
        $data = [];

        $companyId = getScopeId();

        if(in_array('hover', (array)$input['name'])) {
            $hover = HoverClient::where('company_id', $companyId)->first();
            $data['hover'] =  ($hover) ? $this->response->item($hover, new HoverClientTransformer) : null;
        }

        if(in_array('quickbook', (array)$input['name'])) {
            $quickbook = QuickBook::where('company_id', $companyId)->whereNotNull('quickbook_id')->first();
            $data['quickbook'] =  ($quickbook) ? $this->response->item($quickbook, new QuickbookTransformer) : null;
        }
        if(in_array('quickbook_pay', (array)$input['name'])) {
            $quickbook = QuickBook::where('company_id', $companyId)->whereNotNull('quickbook_id')->first();
            $data['quickbook_pay'] =  ($quickbook) ? $this->response->item($quickbook, new QuickbookPayTransformer) : null;
        }
        if(in_array('eagleview', (array)$input['name'])) {
            $eagleview = EVClient::where('company_id', $companyId)->first();
            $data['eagleview'] =  ($eagleview) ? $this->response->item($eagleview, new EagleViewClientTransformer) : null;
        }
        if(in_array('quickbook_desktop', (array)$input['name'])) {
            $qbDesktop = QBDesktopUser::where('company_id', $companyId)
                ->whereSetupCompleted(true)
                ->exists();
            $data['quickbook_desktop'] =  (bool)$qbDesktop;
        }

        if(in_array('facebook', (array)$input['name'])) {
			$data['facebook'] =  CompanyNetwork::whereNetwork(CompanyNetwork::FACEBOOK)
				->where('company_id', getScopeId())
				->exists();
			$data['facebook_page'] =  NetworkMeta::whereMetaKey(CompanyNetwork::PAGES)->where('network_id', function($query){
					$query->select('id')->from('company_networks')->where('company_id', getScopeId())
					->where('network', CompanyNetwork::FACEBOOK);
				})->exists();
		}

		if(in_array('linkedin', (array)$input['name'])) {
			$data['linkedin'] =  CompanyNetwork::whereNetwork(CompanyNetwork::LINKEDIN)
				->where('company_id', getScopeId())
				->exists();
		}

		if(in_array('twitter', (array)$input['name'])) {
			$data['twitter'] =  CompanyNetwork::whereNetwork(CompanyNetwork::TWITTER)
				->where('company_id', getScopeId())
				->exists();
		}if(in_array('facebook', (array)$input['name'])) {
			$data['facebook'] =  CompanyNetwork::whereNetwork(CompanyNetwork::FACEBOOK)
				->where('company_id', getScopeId())
				->exists();
			$data['facebook_page'] =  NetworkMeta::whereMetaKey(CompanyNetwork::PAGES)->where('network_id', function($query){
					$query->select('id')->from('company_networks')->where('company_id', getScopeId())
					->where('network', CompanyNetwork::FACEBOOK);
				})->exists();
		}

		if(in_array('linkedin', (array)$input['name'])) {
			$data['linkedin'] =  CompanyNetwork::whereNetwork(CompanyNetwork::LINKEDIN)
				->where('company_id', getScopeId())
				->exists();
		}

		if(in_array('twitter', (array)$input['name'])) {
			$data['twitter'] =  CompanyNetwork::whereNetwork(CompanyNetwork::TWITTER)
				->where('company_id', getScopeId())
				->exists();
        }

        if(in_array('srs', (array)$input['name'])) {
            $srs = Supplier::srs();
            $data['srs'] =  (bool)$srs->companySupplier;
        }
        if(in_array('companycam', (array)$input['name'])) {
            $companyCam = CompanyCamClient::whereCompanyId($companyId)->first();
            $data['companycam'] = (bool)$companyCam;
        }

        return ApiResponse::success([
            'data' => $data,
        ]);
    }

    public function getBillingInfoFromRecurly()
    {
        $company = Company::findOrFail(getScopeId());

		$billingDetails = $this->recurlyService->getBillingDetails($company->recurly_account_code);

        return ApiResponse::success(['data' => $billingDetails]);
    }

    /**
	 * create a new company for existing user
	 *
	 * POST - /companies/create
	 *
	 * @return response
	 */
	public function create()
	{
		$input = Request::all();
		$validator = Validator::make($input, array_merge(Company::getCreateRules(), Company::getBillingDetailRules()));
		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$owner = Auth::user();
		if(!$owner->isOwner()) {
			return ApiResponse::errorForbidden();
		}
		try {
			$company = $this->service->create($owner, $input);
			return ApiResponse::success([
				'message' => trans('response.success.created', ['attribute' => 'Company']),
				'data' => $this->response->item($company, new CompaniesTransformer)
			]);
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
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
        } catch (Exception $e) {
            //handle exception..
        }
    }
}
