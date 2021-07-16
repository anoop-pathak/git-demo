<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laracasts\Presenter\PresentableTrait;
use App\Models\User as User;

class Company extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;
    use PresentableTrait;

    protected $presenter = \App\Presenters\CompanyPresenter::class;

    protected $fillable = [
        'name',
        'office_address',
        'office_address_line_1',
        'office_city',
        'office_state',
        'office_zip',
        'office_country',
        'office_phone',
        'office_fax',
        'logo',
        'timezone_id',
        'office_email',
        'additional_email',
        'additional_phone',
        'account_manager_id',
        'company_country',
        'license_number'
    ];

    protected $rules = [
        'office_address' => 'required',
        'office_address_line_1' => 'required',
        'office_city' => 'required',
        'office_state_id' => 'required',
        'office_zip' => 'required',
        'office_country_id' => 'required',
        'office_email' => 'required|email',
        'office_phone' => 'required',
        'account_manager_id' => 'required'
    ];

    protected static $updateRules = [
        'office_address' => 'required',
        'office_city' => 'required',
        'office_state_id' => 'required',
        'office_zip' => 'required',
        'office_country_id' => 'required',
        'office_phone' => 'required',
        'office_email' => 'required|email',
        'license_numbers'  =>  'array|max:3',
    ];

    protected $assignRules = [
        'trades' => 'required',
        // 'job_types'      => 'required',
    ];

    protected $addNoteRules = [
        'notes' => 'required',
    ];

    protected $uploadLogoRule = [
        'logo' => 'required|mimes:jpeg,png',
    ];

    protected $saveCompanyStatesRules = [
        'company_id' => 'required',
        'company_country' => 'required',
        'states' => 'required|array'
    ];

    protected $createRules = [
        'company_details.company_name'   => 'required|max:100',
        'company_details.phone'          => 'required',
        'company_details.email'          => 'required|email',
        'company_details.address'        => 'required',
        'company_details.city'           => 'required',
        'company_details.state_id'       => 'required',
        'company_details.country_id'     => 'required',
        'company_details.zip'            => 'required',
    ];

    protected $billingDetailRules = [
        'billing_details.token'        => 'required',
        'billing_details.product_id'   => 'required',
        'billing_details.email'        => 'required|email',
    ];

    protected $billingAddressRules = [
        'billing_address.address'      => 'required',
        'billing_address.city'         => 'required',
        'billing_address.state_id'     => 'required',
        'billing_address.country_id'   => 'required',
        'billing_address.zip'          => 'required',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public static function getUpdateRules()
    {
        $input = \Request::all();
        $license_numbers = [];

        if (ine($input,'license_numbers') && is_array($input['license_numbers'])) {
            foreach ($input['license_numbers'] as $key => $value) {
                $license_numbers["license_numbers.$key.position"] = 'required|in:1,2,3';
                $license_numbers["license_numbers.$key.number"] = 'required';

            }
        }
        return array_merge(self::$updateRules, $license_numbers);
    }

    protected function getAssignRules()
    {
        return $this->assignRules;
    }

    protected function getAddNotesRules()
    {
        return $this->addNoteRules;
    }

    protected function getUploadLogoRule()
    {
        return $this->uploadLogoRule;
    }

    protected function getSaveCompanyStatesRules()
    {
        return $this->saveCompanyStatesRules;
    }

    protected function getCreateRules()
    {
        return $this->createRules;
    }

    protected function getBillingDetailRules()
    {
        return $this->billingDetailRules;
    }

    public function companyCountry()
    {
        return $this->belongsTo(Country::class, 'company_country');
    }

    public function timezone()
    {
        return $this->belongsTo(Timezone::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'office_state');
    }

    public function country()
    {
        return $this->belongsTo(Country::class, 'office_country');
    }

    public function accountManager()
    {
        return $this->belongsTo(AccountManager::class, 'account_manager_id');
    }

    public function companyNetworks()
    {
        return $this->hasMany(CompanyNetwork::class, 'company_id');
    }

    public function licenseNumbers()
    {
        return $this->hasMany(CompanyLicenseNumber::class, 'company_id')->orderBy('position', 'asc');
    }

    public function users()
    {
        return $this->hasMany(User::class)
            ->where('group_id', '!=', User::GROUP_SUB_CONTRACTOR)
            ->notHiddenUser();
    }

    public function authority()
    {
        $groups = [User::GROUP_OWNER, User::GROUP_ADMIN];

        return $this->hasMany(User::class)
            ->whereIn('group_id', $groups);
    }

    // owner..
    public function subscriber()
    {
        return $this->hasOne(User::class)->where('group_id', '=', User::GROUP_OWNER);
    }

    public function admins()
    {
        return $this->hasMany(User::class)->where('group_id', '=', User::GROUP_ADMIN);
    }

    public function subcontractors()
    {
        return $this->hasMany(User::class)->whereIn('group_id', [User::GROUP_SUB_CONTRACTOR, User::GROUP_SUB_CONTRACTOR_PRIME]); 
    }
    
    public function primeSubcontractors()
    {
        return $this->hasMany(User::class)->where('group_id','=',User::GROUP_SUB_CONTRACTOR_PRIME); 
    }

    public function anonymous()
    {
        return $this->hasOne(User::class)->where('group_id', '=', User::GROUP_ANONYMOUS);
    }

    public function companyNetwork()
    {
        return $this->hasMany(CompanyNetwork::class);
    }

    public function allUsers()
    {
        return $this->hasMany(User::class);
    }

    public function notes()
    {
        return $this->hasMany(CompanyNote::class)->orderBy('created_at', 'desc');
    }

    public function states()
    {
        return $this->belongsToMany(State::class, 'company_state', 'company_id', 'state_id')->withPivot('tax_rate', 'material_tax_rate', 'labor_tax_rate');
    }

    public function trades()
    {
        return $this->belongsToMany(Trade::class, 'company_trade', 'company_id', 'trade_id')->orderBy('trades.id', 'asc');
    }

    public function jobTypes()
    {
        return $this->belongsToMany(JobType::class, 'company_job_type', 'company_id', 'job_type_id');
    }

    public function billing()
    {
        return $this->hasOne(CompanyBilling::class);
    }

    public function subscription()
    {
        return $this->hasOne(Subscription::class);
    }

    public function redeemedCoupons()
    {
        return $this->hasMany(RedeemedCoupon::class);
    }

    public function setupActions()
    {
        return $this->belongsTomany(SetupAction::class, 'company_setup_action', 'company_id', 'setup_action_id');
    }

    // EgleView Client
    public function evClient()
    {
        return $this->hasOne(EVClient::class, 'company_id');
    }

    // SkyMeasure Client
    public function smClient()
    {
        return $this->hasOne(SMClient::class, 'company_id');
    }

    public function quickbook()
    {
        return $this->hasOne(QuickBook::class)->whereNotNull('quickbook_id');
    }

    public function quickbookDesktop()
    {
        return $this->hasOne(QBDesktopUser::class)
            ->whereNotNull('company_id')
            ->whereSetupCompleted(true);
    }

    public function meta()
    {
        return $this->hasMany(CompanyMeta::class);
    }

    public function workTypes()
    {
        return $this->hasMany(JobType::class)->whereType(JobType::WORK_TYPES);
    }

    public function subscriberResource()
    {
        return $this->hasOne(CompanyMeta::class)->where('key', CompanyMeta::SUBSCRIBER_RESOURCE_ID);
    }

    public function customers()
    {
        return $this->hasMany(Customer::class);
    }

    public function latestProposal()
    {
        return $this->hasOne(Proposal::class, 'company_id')->latest();
    }

    public function latestEstimate()
    {
        return $this->hasOne(Estimation::class)->latest();
    }

    public function googleClient()
    {
        return $this->hasOne(GoogleClient::class);
    }

    public function companyCamClient()
    {
        return $this->hasOne(CompanyCamClient::class);
    }


    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'company_supplier', 'company_id', 'supplier_id');
    }

    public function productionBoards()
    {
        return $this->hasMany(ProductionBoard::class);
    }

    public function templates()
    {
        return $this->belongsToMany(Template::class, 'worksheet_templates', 'company_id', 'template_id');
    }

    public function hoverClient()
    {
        return $this->hasOne(HoverClient::class, 'company_id');
    }

    public function companyLogos()
    {
        return $this->hasOne(CompanyLogo::class, 'company_id');
    }

    public function subscriberStageAttribute()
    {
        return $this->belongsToMany(SubscriberStageAttribute::class, 'subscriber_stages', 'company_id', 'subscriber_stage_attribute_id')
            ->whereNull('subscriber_stages.deleted_at')
            ->orderBy('id', 'desc')
            ->withPivot('created_at')
            ->take(1);
    }

    public function scopeName($query, $name)
    {
        return $query->where('companies.name', 'like', '%' . $name . '%');
    }

    public function scopeAccountManager($query, $accountManagersIds)
    {

        if (!is_array($accountManagersIds)) {
            $accountManagersIds = (array)$accountManagersIds;
        }

        return $query->whereIn('account_manager_id', $accountManagersIds);
    }

    public function scopeState($query, $state)
    {
        return $query->where('office_state', $state);
    }

    public function scopeActivated($query, $status)
    {
        return $query->whereIn('companies.id', function ($query) use ($status) {
            $query->select('company_id')->from('subscriptions');

            // if($status == Subscription::ACTIVE) {
            //     $query->whereIn('status', [Subscription::TRIAL, Subscription::ACTIVE]);
            // }else {
            $query->where('status', $status);
            // }
        });
    }

    public function scopeSubscribers($query, $proudctId, $status = Subscription::ACTIVE)
    {
        return $query->whereIn('companies.id', function ($query) use ($proudctId, $status) {
            $query->select('company_id')->from('subscriptions')
                ->whereProductId($proudctId);

            // if($status == Subscription::ACTIVE) {
            //     $query->whereIn('status', [Subscription::TRIAL, Subscription::ACTIVE]);
            // }else {
            $query->whereStatus($status);
            // }
        });
    }

    public function scopeActivationDateRange($query, $start, $end)
    {
        return $query->whereIn('companies.id', function ($query) use ($start, $end) {
            $query->select('company_id')->from('subscriptions');
            if ($start) {
                $query->whereRaw("DATE_FORMAT(activation_date, '%Y-%m-%d') >= '$start'");
            }
            if ($end) {
                $query->whereRaw("DATE_FORMAT(activation_date, '%Y-%m-%d') <= '$end'");
            }
        });
    }

    public function scopeStatusUpdatedAt($query,$start,$end)
    {
        return $query->whereIn('companies.id', function($query) use($start,$end){
           $query->select('company_id')->from('subscriptions');
            if($start) {
                $query->whereRaw("DATE_FORMAT(status_updated_at, '%Y-%m-%d') >= '$start'");
            }
            if($end) {
                $query->whereRaw("DATE_FORMAT(status_updated_at, '%Y-%m-%d') <= '$end'");   
            }
        });
    }

    public static function getCompanyById($company_id)
    {
        $company = self::where(['id' => $company_id])->first();
        return $company;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getAdditionalPhoneAttribute($value)
    {
        return json_decode($value);
    }

    public function setAdditionalPhoneAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['additional_phone'] = json_encode($value);
    }

    public function getAdditionalEmailAttribute($value)
    {
        return json_decode($value);
    }

    public function setAdditionalEmailAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['additional_email'] = json_encode($value);
    }

    public function applicableSubscriptionPlan($productId)
    {

        $plan = DB::table('subscription_plans')
            ->where('max', '>=', $this->users->count())
            ->where('min', '<=', $this->users->count())
            ->where('product_id', '=', $productId)
            ->where('cycles', '=', 'unlimited')
            ->first();

        return $plan;
    }

    public function isActive()
    {
        if (!$this->subscription) {
            return false;
        }
        return ($this->subscription->status == true);
    }

    public static function boot()
    {
        parent::boot();

        static::deleting(function ($company) {
            $company->allUsers()->delete();
        });
    }
}
