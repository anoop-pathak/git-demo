<?php

namespace App\Models;

use App\Helpers\SecurityCheck;
use App\Services\Grid\SortableTrait;
use Solr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laracasts\Presenter\PresentableTrait;
use Nicolaslopezj\Searchable\SearchableTrait;
use App\Services\Masking\DataMasking;
use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;

class Customer extends BaseModel implements SynchEntityInterface
{

    use SortableTrait;
    use SoftDeletes;
    use PresentableTrait;
    use SearchableTrait;
    use QboSynchableTrait;
    use QbdSynchableTrait;

    protected $presenter = \App\Presenters\CustomerPresenter::class;

    protected $dates = ['deleted_at'];

    protected $appends = ['full_name', 'full_name_mobile'];

    protected $fillable = [
        'first_name',
        'last_name',
        'company_name',
        'email',
        'additional_emails',
        'rep_id',
        'address_id',
        'billing_address_id',
        'created_by',
        'last_modified_by',
        'company_id',
        'referred_by',
        'referred_by_type',
        'referred_by_note',
        'call_required',
        'appointment_required',
        'note',
        'quickbook_id',
        'quickbook_sync_token',
        'is_commercial',
        'management_company',
        'property_name',
        'solr_sync',
        'quickbook_sync',
        'canvasser',
        'call_center_rep',
        'source_type',
        'origin',
        'quickbook_sync_status',
        'disable_qbo_sync'
    ];

    const TYPE_ZAPIER = 'Zapier';
    const REFERRED_BY_TYPE = 'referral';

    /**
     * Searchable rules.
     *
     * @var array
     */
    protected $searchable = [];

    protected $changeRepresentativeRules = [
        'customer_id' => 'required',
        // 'rep_id' => 'required'
    ];

    protected $deleteRules = [
        'password' => 'required',
        'note' => 'required',
    ];

    protected $customerCommunicationRules = [
        'type' => 'required|in:call,appointment',
        'status' => 'required|boolean'
    ];


    protected function getChangeRepresentativeRules()
    {
        return $this->changeRepresentativeRules;
    }

    protected function getDeleteRules()
    {
        return $this->deleteRules;
    }

    protected function getCustomerCommunicationRules()
    {
        return $this->customerCommunicationRules;
    }

    /**
     * JobsCounts Column
     *
     * @var int
     */
    protected $jobsCount = null;

    public function getAdditionalEmailsAttribute($value)
    {
        $dataMasking = new DataMasking;
        return $dataMasking->maskEmail(json_decode($value));
    }

    public function setAdditionalEmailsAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['additional_emails'] = json_encode($value);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getDeletedAtAttribute($value)
    {
        if (!$value) {
            return null;
        }
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getFullNameAttribute()
    {
        if (empty($this->last_name)) {
            return $this->first_name;
        }

        return $this->first_name . ' ' . $this->last_name;
    }

    public function getFullNameMobileAttribute()
    {
        if (empty($this->last_name)) {
            return $this->first_name;
        }

        return $this->first_name . ' ' . $this->last_name;
    }

    // mask email in case of sub contractor login
    public function getEmailAttribute($value)
    {
        $dataMasking = new DataMasking;
        $email = $dataMasking->maskEmail($value);
        return $this->attributes['email'] = $email;
    }

    public function getQBDeskotopFullName()
    {
        return $this->first_name . ' ' . $this->last_name . ' (' . $this->id . ')';
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucfirst(trim($value));
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucfirst(trim($value));
    }

    public function getCompanyName()
    {
        if ($this->is_commercial) {
            return $this->first_name;
        }

        return $this->company_name;
    }

    public function getFirstName()
    {
        if ($this->is_commercial) {
            return null;
        }

        return $this->first_name;
    }

    public function getQBDCustomerName()
    {
        if (!$this->is_commercial) {
            $fullName = $this->first_name . ' ' . $this->last_name . ' ' . '(' . $this->id . ')';

            return $fullName;
        }

        return $this->first_name . ' ' . '(' . $this->id . ')';
    }

     /**
     * Get Quickbook Log Display Name
     * @return Display Name
     */
    public function getLogDisplayName()
    {
        if(!$this->is_commercial) {
            $fullName = $this->first_name .' '.$this->last_name .' '.'(' . $this->id.')';

            return $fullName;
        }

        return  $this->first_name.' '.'(' . $this->id.')';
    }

    public function getCustomerId(){
        return $this->id;
    }

    public function createOrUpdateMeta($key, $value)
	{
        $meta = CustomerMeta::where('customer_id', $this->id)->where('meta_key', $key)->first();
        if(!$meta) {
            $meta = new CustomerMeta;
            $meta->customer_id = $this->id;
            $meta->meta_key = $key;

            if(Auth::check()) {
                $meta->created_by = Auth::user()->id;
            }
        }
        $meta->meta_value = $value;
        $meta->save();
        return $meta;
    }

    /*************************** Customer Relationships *****************************/

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function customerMeta() {
		return $this->hasMany(CustomerMeta::class);
	}

    public function billing()
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function rep()
    {
        return $this->belongsTo(User::class, 'rep_id');
    }

    public function jobs()
    {
        $relation = $this->hasMany(Job::class, 'customer_id', 'id');
        if (config('disable_multi_job_on_mobile')) {
            $relation->excludeParent();
        }
        $relation->withoutArchived()->select('jobs.*');

        return $relation->excludeProjects();
    }

    public function allJobs()
    {
        return $this->hasMany(Job::class, 'customer_id', 'id')->excludeProjects();
    }

    public function withArchivedJobs()
    {
        $relation = $this->hasMany(Job::class, 'customer_id', 'id');

        if(config('disable_multi_job_on_mobile')){
            $relation->excludeParent();
        }
        $relation->select('jobs.*');

        return $relation->excludeProjects();
    }

    public function phones()
    {
        return $this->hasMany(Phone::class, 'customer_id', 'id');
    }

    public function firstPhone()
    {
        return $this->hasOne(Phone::class, 'customer_id', 'id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'customer_id', 'id')->recurring();
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
    }

    public function todayAppointments()
    {
        return $this->appointments()->today();
    }

    public function upcomingAppointments()
    {
        return $this->appointments()->upcoming();
    }

    public function flags()
    {
        $relation = $this->belongsToMany(Flag::class, 'customer_flags', 'customer_id', 'flag_id');

        $relation->whereNotIn('flags.id', function ($query) {
            $query->select('flag_id')
                ->from('comapny_deleted_flags')
                ->where('company_id', getScopeId());
        });

        return $relation;
    }

    public function contacts()
    {
        return $this->hasMany(CustomerContact::class, 'customer_id');
    }

    public function vendorbill(){
        return $this->hasMany(VendorBill::class);
    }

    public function refund(){
        return $this->hasMany(JobRefund::class);
    }

    public function secondaryNameContact()
    {
        return $this->hasOne(CustomerContact::class, 'customer_id')->select('first_name', 'last_name', 'customer_id');
    }

    // users with customer access
    public function users()
    {
        return $this->belongsToMany(User::class, 'customer_user', 'customer_id', 'user_id');
    }

    public function referredBy()
    {
        if ($this->referred_by_type == 'customer') {
            return $this->referredByCustomer;
        }

        if ($this->referred_by_type == 'referral') {
            return $this->referredByReferral;
        }

        return null;
    }

    public function referredByCustomer()
    {
        return $this->belongsTo(Customer::class, 'referred_by')->withTrashed();
    }

    public function referredByReferral()
    {
        return $this->belongsTo(Referral::class, 'referred_by')->withTrashed();
    }

    public function invoices()
    {
        return $this->hasMany(JobInvoice::class);
    }

    public function jobCredits()
    {
        return $this->hasMany(JobCredit::class);
    }

    public function payments()
    {
        return $this->hasMany(JobPayment::class)
            ->with('invoicePayments')
            ->whereNull('canceled')
            ->orderBy('payment', 'desc');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customFields()
    {
        return $this->hasMany(CustomerCustomField::class);
    }

    public function unlinkCustomer(){
        return $this->hasOne(QuickbookUnlinkCustomer::class)->where('type', QuickbookUnlinkCustomer::QBO);
    }

    public function unlinkQBDCustomer(){
        return $this->hasOne(QuickbookUnlinkCustomer::class)->where('type', QuickbookUnlinkCustomer::QBD);
    }

    /************************* End Customer Relationships ***************************/

    public function scopeFlags($query, $flags)
    {
        $query->whereIn('customers.id', function ($query) use ($flags) {
            $query->select('customer_id')->from('customer_flags')->whereIn('flag_id', (array)$flags);
        });
    }

    public function scopeRep($query, $repIds)
    {
        $query->whereIn('rep_id', (array)$repIds);
    }

    public function scopeJobRep($query, $repIds)
    {
        $query->whereHas('jobs', function ($query) use ($repIds) {
            $query->wherHas('reps', function ($query) use ($repIds) {
                $query->whereIn('rep_id', (array)$repIds);
            });
        });
    }

    public function scopeDeletedCustomers($query, $from, $to)
    {
        $query->onlyTrashed();
        $query->where(function($query) use($from, $to) {
            if($from) {
                $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('customers.deleted_at').", '%Y-%m-%d') >= '{$from}'");
            }
            if($to) {
                $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('customers.deleted_at').", '%Y-%m-%d') <= '{$to}'");
            }
        });
    }

    /**
     * Get Users jobs (if rep id is null then check current users permission)
     */
    public function scopeOwn($query, $repId = null)
    {
        if (!$repId) {
            if (!SecurityCheck::RestrictedWorkflow()) {
                return $query;
            }
            $repId = \Auth::id();
        }

        $query->where(function ($query) use ($repId) {
            if(!\Auth::user()->isSubContractorPrime()) {
                $query->whereRepId($repId)
                ->orWhereIn('customers.id', function($query) use($repId) {
                    $query->select('customer_id')->from('customer_user')->where('user_id', $repId);
                });
            }

            $query->orWhereHas('jobs', function($query) use($repId){
                $query->own($repId);
            });
                  
        });
        $query->with([
            'jobs' => function ($query) use ($repId) {
                $query->own($repId);
            }
        ]);
    }

    public function scopeJobContactPerson($query, $name)
    {
        $query->where(function ($query) use ($name) {
            $query->whereHas('jobs.contacts', function ($query) use ($name) {
                $query->whereRaw("CONCAT(contacts.first_name,' ',contacts.last_name) LIKE ?", ['%' . $name . '%']);
            });

            $query->orWhere(function ($query) use ($name) {
                $query->whereRaw("CONCAT(customers.first_name,' ',customers.last_name) LIKE ?", ['%' . $name . '%']);
                $query->whereHas('jobs', function ($query) {
                    $query->where('jobs.contact_same_as_customer', 1);
                });
            });
        });
    }

    /**
     * Commercial Scope
     * @param  queryBuilder $query queryBuilder
     * @param  bool $isCommercial isCommercial
     * @return void
     */
    public function scopeCommercial($query, $isCommercial)
    {
        if (isFalse($isCommercial)) {
            $query->where('is_commercial', false);
        } elseif (isTrue($isCommercial)) {
            $query->where('is_commercial', true);
        }
    }

    public function scopeFirstName($query, $firstName)
    {
        $query->where(function ($query) use ($firstName) {
            $query->whereHas('contacts', function ($query) use ($firstName) {
                $query->where('first_name', 'Like', '%' . $firstName . '%');
            });

            $query->orWhere(function ($query) use ($firstName) {
                $query->where('first_name', 'Like', '%' . $firstName . '%');
                $query->orderByRaw(orderByCaseQuery('first_name', $firstName));
            });
        });
    }

    public function scopeLastName($query, $lastName)
    {
        $query->where(function ($query) use ($lastName) {
            $query->whereHas('contacts', function ($query) use ($lastName) {
                $query->where('last_name', 'Like', '%' . $lastName . '%');
            });

            $query->orWhere(function ($query) use ($lastName) {
                $query->where('last_name', 'Like', '%' . $lastName . '%');
                $query->orderByRaw(orderByCaseQuery('last_name', $lastName));
            });
        });
    }

    /**
     * Searchable name rules.
     *
     * @var array
     */
    public function scopeNameSearch($query, $keyword, $companyId)
    {

        if (config('system.enable_solr')) {
            $ids = Solr::customerSearchByName($keyword);
            $placeHolders = implode(',', $ids);
            $query->whereIn('customers.id', $ids);
            if ($placeHolders) {
                $query->orderByRaw(DB::raw("FIELD(customers.id, $placeHolders)"));
            }
        } else {
            $this->searchable = [
                'columns' => [
                    'customers.first_name' => 10,
                    'customers.last_name' => 10,
                    'customer_contacts.first_name' => 10,
                    'customer_contacts.last_name' => 10
                ],
                'joins' => [
                    'customer_contacts' => ['customer_contacts.customer_id', 'customers.id']
                ],
            ];

            $query->search($keyword);
        }
    }

    /**
     * Searchable keywords rules.
     *
     * @var array
     */
    public function scopeKeywordSearch($query, $keyword, $companyId)
    {
        if (config('system.enable_solr')) {
            $ids = Solr::customerSearch($keyword);
            $placeHolders = implode(',', $ids);
            $query->whereIn('customers.id', $ids);

            if ($placeHolders) {
                $query->orderByRaw(DB::raw("FIELD(customers.id, $placeHolders)"));
            }
        } else {
            $this->searchable = [
                'columns' => [
                    'first_name' => 430,
                    'last_name' => 420,
                    'company_name' => 175,
                    'phones.number' => 170,
                    'email' => 166,
                    'address' => 165,
                    'address_line_1' => 164,
                    'city' => 163,
                    'states.name' => 162,
                    'zip' => 160,
                    'states.code' => 160,
                ],
            ];

            $query->search($keyword, null, true);
        }
    }

    public function scopeSubOnly($query, $subIds)
    {
        if(!self::isJoined($query, 'jobs')) {
            $query->join('jobs', 'jobs.customer_id', '=', 'customers.id');
        }
         $query->join('job_sub_contractor', function($query) use($subIds) {
            $query->on('job_sub_contractor.job_id', '=', 'jobs.id')
                ->where('job_sub_contractor.sub_contractor_id','=', (array) $subIds);
        });
    }

    public function scopeCreatedDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate('customers.created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('customers.created_at', '<=', $endDate);
        }
    }

    public function scopeUpdatedDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate('customers.updated_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->whereDate('customers.updated_at', '<=', $endDate);
        }
    }

    public function scopeNoteSearch($query, $note)
    {
        $this->searchable = [
            'columns' => [
                'customers.note' => 1
            ]
        ];
         $query->search($note, null, true);
         $query->orderBy('relevance', 'desc');
    }

    public function scopeDivisions($query, $divisionIds, $withArchivedJobs = false)
    {
        $query->whereIn('customers.id', function($query) use($divisionIds, $withArchivedJobs) {
            $query->select('customer_id')->from('jobs')
            ->whereIn('jobs.division_id', (array) $divisionIds)
            ->where('jobs.company_id', getScopeId())
            ->whereNull('jobs.deleted_at');

            if(!$withArchivedJobs) {
                $query->whereNull('jobs.archived');
            }
        });
    }

    public function scopePhones($query, $phones = [])
    {
        if(empty($phones)) return;
        $query->whereIn('customers.id', function($query) use($phones){
            $query->select('customer_id')->from('phones')->where('number', $phones);
        });
    }

    public function jobsNotSynced()
    {
        $relation = $this->hasMany(Job::class, 'customer_id', 'id');

        $relation->withoutArchived()
            ->whereNull('quickbook_id')
            ->select('jobs.*');

        return $relation->excludeProjects();
    }

    public function QBOCustomers()
    {
        return $this->belongsToMany(QBOCustomer::class, 'quickbook_sync_customers', 'customer_id', 'qb_id')
            ->where('qbo_customers.company_id', getScopeId());
    }

    public function mappedJobs()
    {
        return $this->hasMany(QuickbookMappedJob::class, 'customer_id', 'id');
    }

    public function qbCustomer()
    {
        return $this->hasOne(QBOCustomer::class, 'qb_id', 'quickbook_id')
            ->whereNull('qbo_customers.qb_parent_id')
            ->where('qbo_customers.company_id', getScopeID());
    }

    public function qbdCustomer()
    {
        return $this->hasOne(QBOCustomer::class, 'qb_id', 'qb_desktop_id')
            ->whereNull('qbo_customers.qb_parent_id')
            ->where('qbo_customers.company_id', getScopeID());
    }

    public static function validationRules($scopes = [])
    {
        $rules = [
            'first_name' => 'required',
            'last_name' => 'required_without:is_commercial|required_if:is_commercial,0',
            'email' => 'email|nullable',
            'appointment_required' => 'boolean',
            'call_required' => 'boolean',
            'flag_ids' => 'array',
            'is_commercial' => 'boolean',
        ];

        if(Auth::user()->isOpenAPIUser()) {
            
            $rules = array_merge($rules, [
                'last_name' => 'required'
            ]);

            if (request()->get('is_commercial') == 1) {
                $rules = array_merge($rules, [
                    'first_name' => 'nullable',
                    'last_name' => 'nullable',
                    'company_name' => 'required',
                ]);
            }
        }

        if (in_array('address', $scopes)) {
            $rules = array_merge($rules, [
                'address.address' => 'required',
                'address.city' => 'required',
                'address.state_id' => 'required',
                'address.country_id' => 'required',
                'address.zip' => 'required',
            ]);
        }

        if (in_array('billing', $scopes)) {
            $rules = array_merge($rules, [
                'billing.address' => 'required',
                'billing.city' => 'required',
                'billing.state_id' => 'required',
                'billing.country_id' => 'required',
                'billing.zip' => 'required',
            ]);
        }

        if (in_array('referredBy', $scopes)) {
            $rules = array_merge($rules, [
                'referred_by_type' => 'required|in:referral,customer,other,website',
                'referred_by_id' => 'required_if:referred_by_type,referral,customer',
                'referred_by_note' => 'required_if:referred_by_type,other'
            ]);
        }

        if (!in_array('phones', $scopes)) {
            $rules = array_merge($rules, [
                'phones' => 'required|array'
            ]);
        }

        if (in_array('phones', $scopes)) {
            $input = \Request::all();
            if(ine($input, 'phones') && is_array($input['phones'])) {
                foreach ($input['phones'] as $key => $value) {
                    $rules['phones.' . $key . '.label']  = 'required|in:home,cell,phone,office,fax,other';
                    $rules['phones.' . $key . '.number']  = 'required|customer_phone:8,12';
                }
            }
        }

        if (in_array('customer_contacts', $scopes)) {
            for ($i = 0; $i < $scopes['customer_contacts_count']; $i++) {
                $rules['customer_contacts.' . $i . '.first_name'] = 'required';
                $rules['customer_contacts.' . $i . '.last_name'] = 'required';
            }
        }

        if (in_array('canvasser_id', $scopes)) {
            $rules = array_merge($rules, [
                'canvasser_id' => 'exists:users,id,company_id,'.getScopeId()
            ]);
        }

        if (in_array('call_center_rep_id', $scopes)) {
            $rules = array_merge($rules, [
                'call_center_rep_id' => 'exists:users,id,company_id,'.getScopeId()
            ]);
        }

        if (in_array('rep_id', $scopes)) {
            $rules = array_merge($rules, [
                'rep_id' => 'exists:users,id,company_id,'.getScopeId()
            ]);
        }

        return $rules;
    }
}
