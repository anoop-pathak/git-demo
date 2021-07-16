<?php

namespace App\Models;

use FlySystem;
use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Zizaco\Entrust\Traits\EntrustUserTrait;
use Laravel\Passport\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Request;
use Illuminate\Notifications\Notifiable;
use App\Notifications\ResetPasswordNotification;
use App\Services\Grid\DivisionTrait;

class User extends Authenticatable
{

    use HasApiTokens, SortableTrait, EntrustUserTrait, Notifiable, DivisionTrait, SoftDeletes {
        SoftDeletes::restore insteadof EntrustUserTrait;
    }

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'users';
    protected $appends = ['full_name', 'full_name_mobile'];

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'group_id',
        'company_id',
        'active',
        'hire_date',
        'company_name',
        'note',
        'rating',
        'resource_id',
        'active',
        'color',
        'data_masking',
        'all_divisions_access'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = ['password', 'remember_token'];

    const GROUP_SUPERADMIN = 1;
    const GROUP_ADMIN = 2;
    const GROUP_STANDARD_USER = 3;
    const GROUP_SUB_CONTRACTOR = 4;
    const GROUP_OWNER = 5;
    const GROUP_ANONYMOUS = 6;
    const GROUP_LABOR = 7;
    const GROUP_SUB_CONTRACTOR_PRIME = 8;
    const GROUP_OPEN_API = 9;

    protected static $createRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'password' => 'required|min:6|confirmed',
        'password_confirmation' => 'required|min:6',
        'email' => 'required|email|unique:users,email,NULL,id,deleted_at,NULL',
        'group_id' => 'required',
    ];

    protected static $updateRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'password' => 'min:6|confirmed',
        'password_confirmation' => 'min:6',
        // 'group_id'				=>	'required',
        'rating' => 'numeric|min:0|max:5',
    ];

    protected static $nonLoggableUserRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'email',
        'group_id' => 'required',
        'rating' => 'numeric|min:0|max:5',
    ];

    protected static $authRules = [
        'username' => 'required|email',
        'password' => 'required'
    ];

    protected static $uploadProfilePic = [
        'user_id' => 'required',
        'image' => 'required|mimes:jpeg,png'
    ];

    protected static $forgotPass = [
        'url' => 'required',
        'email' => 'required|email'
    ];

    protected static $changeGroupRules = [
        'group_id' => 'required|in:2,3,4,5,8',
    ];

    protected $systemUserRule = [
        'password' => 'required',
        'company_id' => 'required'
    ];

    protected  $updateCredentialsRules = [
		'email'			=> 'required_without:new_password',
		'old_password'	=> 'required',
		'new_password'	=>	'required_without:email|min:6|confirmed',
		'new_password_confirmation'	=>	'required_with:new_password|min:6',
	];

    protected $importRules = [
		'file'		=> 'required|mime_types:text/plain,text/csv',
		'group_id'	=> 'required|in:3,8'
	];

	protected $resetPasswordRules = [
		'old_password'		=> 'required',
		'new_password'		=> 'required',
		'confirm_password'	=> 'required|same:new_password',
	];

    /*************************** User Relationships  *****************************/

    public function findForPassport($username)
    {
        $credentials = explode(', ', $username);
        $data = [
            'username' => ine($credentials, 0) ? $credentials[0] : null,
            'company_id' => ine($credentials, 1) ? $credentials[1] : null
        ];
        $query = $this->where('email' , $data['username']);
        if (ine($data, 'company_id')) {
            $query->where('company_id', $data['company_id']);
        }
        return $query->first();
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class, 'user_department', 'user_id', 'department_id');
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function profilePic(){
		return $this->hasOne(UserProfile::class)->select('id', 'user_id', 'profile_pic');
	}

    public function googleClient()
    {
        return $this->hasOne(GoogleClient::class);
    }

    public function dropboxClient()
    {
        return $this->hasOne(DropboxClient::class);
    }

    public function googleCalendarClient()
    {
        return $this->googleClient()->calendar();
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class)->recurring();
    }

    public function notifications()
    {
        return $this->belongsToMany(Notification::class, 'notification_recipient', 'user_id', 'notification_id')->where('is_read', false);
    }

    public function devices()
    {
        return $this->hasMany(UserDevice::class);
    }

    public function primaryDevice() {
		return $this->hasOne(UserDevice::class)->where('is_primary_device', 1);
	}

    public function customers()
    {
        return $this->hasMany(Customer::class, 'rep_id');
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_rep', 'rep_id', 'job_id');
    }

    public function jobsAsEstimator()
    {
        return $this->belongsToMany(Job::class, 'job_estimator', 'rep_id', 'job_id');
    }

    public function jobsAsRep()
    {
        return $this->belongsToMany(Job::class, 'job_rep', 'rep_id', 'job_id');
    }

    public function jobsAsLabor()
    {
        return $this->belongsToMany(Job::class, 'job_labour', 'labour_id', 'job_id');
    }

    public function jobsAsSubContractor()
    {
        return $this->belongsToMany(Job::class, 'job_sub_contractor', 'sub_contractor_id', 'job_id');
    }

    public function allJobsAsRepOrEstimator()
    {
        return $this->jobs->merge($this->jobsAsEstimator);
    }

    public function role()
    {
        return $this->belongsToMany(Role::class, 'assigned_roles', 'user_id', 'role_id')->select('name');
    }

    public function permissions()
    {
        return $this->hasMany(UserPermission::class);
    }

    public function signature()
    {
        return $this->hasOne(UserSignature::class);
    }

    public function laborTrades()
    {
        return $this->belongsToMany(Trade::class, 'labor_trade', 'user_id', 'trade_id');
    }

    // labor user financial details..
    public function financialDetails()
    {
        return $this->hasMany(FinancialProduct::class, 'labor_id', 'id');
    }

    public function laborWorkTypes()
    {
        return $this->belongsToMany(JobType::class, 'labor_work_type', 'user_id', 'work_type_id');
    }

    //job commission
    public function commissions()
    {
        return $this->hasMany(JobCommission::class);
    }

    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'user_division', 'user_id', 'division_id')
            ->withTimestamps();
    }

    public function tags() {
		return $this->belongsToMany(Tag::class, 'user_tag', 'user_id', 'tag_id')->withTimestamps();
	}

    public function getUserProfilePic()
    {
        $profile = $this->profile;
        if (empty($profile->profile_pic)) {
            return null;
        }

        return FlySystem::publicUrl(config('jp.BASE_PATH') . $profile->profile_pic);
    }

    public function invitations()
	{
		return $this->hasMany(UserInvitation::class, 'email', 'email')
			->where('user_invitations.company_id', getScopeId());
	}

    /*************************** User Relationships *****************************/

    public static function getCreateRules()
    {
        return self::$createRules;
    }

    public static function getNonLoggableUserRules($id = null)
    {
        $rules = self::$nonLoggableUserRules;
        $rules['email'] = "email";
        return $rules;
    }

    public static function getUpdateRules($id)
    {
        $rules = self::$updateRules;
        $rules['email'] = "email";
        return $rules;
    }

    public static function getAuthRules()
    {
        return self::$authRules;
    }

    public static function getUploadProPicRule()
    {
        return self::$uploadProfilePic;
    }

    public static function getForgotPassRule()
    {
        return self::$forgotPass;
    }

    public static function getChangeGroupRules()
    {
        return self::$changeGroupRules;
    }

    protected static function customMessagesChangeGroup()
    {
        return [
            'in' => 'Please choose a valid group',
        ];
    }

    protected function getSystemUserRule()
    {
        return $this->systemUserRule;
    }

    protected function updateCredentialsRules()
	{
		return $this->updateCredentialsRules;
	}

    protected function getImportRules()
	{
		return $this->importRules;
	}

	protected function getResetPasswordRules()
	{
		return $this->resetPasswordRules;
	}

    public function setPasswordAttribute($value)
    {
        if($value) {
			$this->attributes['password'] = Hash::make($value);
		}
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

    public function newNotification()
    {
        $notification = new Notification;
        $notification->user()->associate($this);
        return $notification;
    }

    /**
     *  set keyword scope for query filtering.
     *
     * @access public
     * @param object of query.
     * @param string of keyword.
     * @return query object.
     */
    public function scopeKeyword($query, $keyword)
    {
        return $query->where(function ($query) use ($keyword) {
            $keyword = '%' . $keyword . '%';
            $query->where('first_name', 'like', $keyword)
                ->orWhere('last_name', 'like', $keyword)
                ->orWhere('email', 'like', $keyword);
        });
    }

    // set Tags scope for query filtering.
    public function scopeTags($query, $tagIds){
        $query->whereIn('users.id',function($query) use($tagIds){
            $query->select('user_id')
                ->from('user_tag')
                ->where('company_id', getScopeId())
                ->whereIn('tag_id', (array)$tagIds);
            });
    }

    // set users scope for query filtering.
    public function scopeWithoutTags($query)
    {
        $query->whereNotIn('users.id',function($query){
            $query->select('user_id')
                ->from('user_tag')
                ->where('company_id', getScopeId());
            });
    }

    public function scopeCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query, $status = true)
    {
        return $query->where('active', $status);
    }

    public function scopeOwner($query)
    {
        return $query->where('group_id', static::GROUP_OWNER);
    }

    public function scopeAdmin($query)
    {
        return $query->where('group_id', static::GROUP_ADMIN);
    }

    public function scopeAnonymous($query)
    {
        return $query->where('group_id', static::GROUP_ANONYMOUS);
    }

    public function scopeStandard($query)
    {
        return $query->whereGroupId(static::GROUP_STANDARD_USER);
    }

    public function scopeDemoUser($query)
    {
        return $query->whereCompanyId(config('jp.demo_subscriber_id'));
    }

    public function scopeCompanyUsers($query, $subscriptionProduct = null, $subscriptionStatus = Subscription::ACTIVE)
    {
        return $query->where('company_id', '!=', 0)
            ->whereNotIn('group_id', [USER::GROUP_OPEN_API])
            ->notHiddenUser()
            ->whereIn('company_id', function ($query) use ($subscriptionProduct, $subscriptionStatus) {
                $query->select('company_id')
                    ->from('subscriptions');

                if ($subscriptionStatus == Subscription::ACTIVE) {
                    $query->whereIn('status', [Subscription::TRIAL, Subscription::ACTIVE]);
                } else {
                    $query->whereStatus($subscriptionStatus);
                }

                if ($subscriptionProduct) {
                    $query->whereProductId($subscriptionProduct);
                }
            });
    }

    public function scopeActiveLoggableCompanyUsers(
        $query,
        $subscriptionProduct = null,
        $subscriptionStatus = Subscription::ACTIVE
    ) {


        $query->join('subscriptions', 'subscriptions.company_id', '=', 'users.company_id')
            ->active()
            ->loggable()// exlude labors/subs
            ->whereNotIn('group_id', [User::GROUP_OPEN_API])
            ->notHiddenUser(); // exclude system user..

        if ($subscriptionStatus == Subscription::ACTIVE) {
            $query->whereIn('subscriptions.status', [Subscription::TRIAL, Subscription::ACTIVE]);
        } else {
            $query->where('subscriptions.status', $subscriptionStatus);
        }

        if ($subscriptionProduct) {
            $query->where('subscriptions.product_id', $subscriptionProduct);
        }

        return $query;
    }

    public function scopeAuthority($query)
    {
        return $query->whereIn('group_id', [
            static::GROUP_OWNER,
            static::GROUP_ADMIN,
            static::GROUP_ANONYMOUS
        ]);
    }

    public function scopeJobUsers($query, $jobId)
    {
        return $query->whereHas('jobsAsEstimator', function ($query) use ($jobId) {
            $query->whereJobId($jobId);
        })->orWhereHas('jobsAsRep', function ($query) use ($jobId) {
            $query->whereJobId($jobId);
        });
    }

    public function isSuperAdmin()
    {
        return $this->group_id == static::GROUP_SUPERADMIN;
    }

    public function isOpenAPIUser()
    {
        return $this->group_id == static::GROUP_OPEN_API;
    }

    public function isAdmin()
    {
        return $this->group_id == static::GROUP_ADMIN;
    }

    public function isOwner()
    {
        return $this->group_id == static::GROUP_OWNER;
    }

    public function isAuthority()
    {
        return (in_array($this->group_id, [
            static::GROUP_OWNER,
            static::GROUP_ADMIN,
            static::GROUP_ANONYMOUS,
        ]));
    }

    public function isAnonymous()
    {
        return $this->group_id == static::GROUP_ANONYMOUS;
    }

    public function isStandardUser()
    {
        return $this->group_id == static::GROUP_STANDARD_USER;
    }

    public function isSubContractor()
    {
        return $this->group_id == static::GROUP_SUB_CONTRACTOR;
    }

    public function isSubContractorPrime()
    {
        return $this->group_id == static::GROUP_SUB_CONTRACTOR_PRIME;
    }

    public function isCompanyUser()
    {
        return (in_array($this->group_id, [
            static::GROUP_OWNER,
            static::GROUP_ADMIN,
            static::GROUP_STANDARD_USER,
            static::GROUP_ANONYMOUS,
            static::GROUP_SUB_CONTRACTOR,
            static::GROUP_SUB_CONTRACTOR_PRIME,
            static::GROUP_OPEN_API
        ]));
    }

    public function isNonLoggable()
    {
        return (in_array($this->group_id, [
            static::GROUP_SUB_CONTRACTOR,
            static::GROUP_LABOR,
        ]));
    }

    public function isAuthorisedUser(){
    	return (in_array($this->group_id, [
    		static::GROUP_OWNER,
    		static::GROUP_ANONYMOUS,
    	]));
    }

    public function dataMaskingEnabled()
    {
        return $this->data_masking;
    }

    /**
     * List User assigned permissions
     * @return [type] [description]
     */
    public function listPermissions()
    {
        // role permissions
        $roles = $this->roles->pluck('id')->toArray();
        $permissions = Permission::whereIn('id', function ($query) use ($roles) {
            $query->select('permission_id')->from('permission_role')->whereIn('role_id', $roles);
        })->pluck('name')->toArray();

        if ($this->isStandardUser()) {
            // merge user level permissions..
            $allow = $this->permissions()->whereAllow(true)->pluck('permission')->toArray();
            $permissions = array_unique(array_merge($permissions, $allow));
            // remove denied permissions..
            $deny = $this->permissions()->whereAllow(false)->pluck('permission')->toArray();
            $permissions = array_diff($permissions, $deny);
        } else {
			$denyPermission[] = 'user_mobile_tracking';
			if($this->isSubContractorPrime()) {
				$denyPermission[] = 'enable_workflow';
			}

			$allow = $this->permissions()->whereAllow(true)->lists('permission');
			$permissions = array_unique(array_merge($permissions, $allow));

			// remove denied permissions..
			$deny  = $this->permissions()->whereAllow(false)->whereIn('permission', $denyPermission)->lists('permission');
			$permissions = array_diff($permissions, $deny);
        }

        return array_values($permissions);
    }

    public function hasPermission($permission){
		$allPermissions = $this->listPermissions();

        return in_array($permission, $allPermissions);
	}

    public function allowedPermissions()
	{
		return $this->permissions()->whereAllow(true)->pluck('permission')->toArray();
	}

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function scopeSuperAdmin($query)
    {
        return $query->where('group_id', static::GROUP_SUPERADMIN)->first();
    }

    public function scopeLoggable($query, $sub = false)
    {
        if ($sub) {
            return $query->whereNotIn('group_id', [
                static::GROUP_LABOR,
            ]);
        }

        if(!Request::get('with_sub_user')) {
            $roles[] = static::GROUP_SUB_CONTRACTOR_PRIME;
        }

        $roles[] = static::GROUP_SUB_CONTRACTOR;
        $roles[] = static::GROUP_LABOR;
        return $query->whereNotIn('group_id',$roles);

    }

    public function scopeLogin($query)
    {
    	if(config('is_mobile')) {
			$query->whereNotIn('group_id', [self::GROUP_SUB_CONTRACTOR, static::GROUP_LABOR]);
		}
    }

    public function scopeActiveLoggable($query)
    {
        $query->active()->loggable();
    }

    public function scopeBillable($query)
    {
        $query->activeLoggable()
            ->whereNull('marked_free'); // exclude free users..
    }

    // non loggable users
    public function scopeLabor($query)
    {
        return $query->whereIn('group_id', [
            static::GROUP_SUB_CONTRACTOR,
            static::GROUP_LABOR
        ]);
    }

    // only labors
    public function scopeOnlyLabors($query)
    {
        return $query->whereIn('group_id', [
            static::GROUP_LABOR
        ]);
    }

    // only sub contractors
    public function scopeOnlySubContractors($query)
    {
        return $query->whereIn('group_id', [
            static::GROUP_SUB_CONTRACTOR,
            static::GROUP_SUB_CONTRACTOR_PRIME,
        ]);
    }

    public function scopeSubContractorPrime($query)
    {
    	$query->where('users.group_id', '=', User::GROUP_SUB_CONTRACTOR_PRIME);
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucfirst($value);
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucfirst($value);
    }

    protected function getNonLoggableGroups()
    {
        return [User::GROUP_LABOR, User::GROUP_SUB_CONTRACTOR];
    }

    /**
     * Send the password reset notification.
     *
     * @param  string  $token
     * @return void
     */
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
	 * get all companies of a user
	 * @return $companies
	 */
	public function allCompanies()
	{
		$companies = Company::whereIn('id', function($query){
			$query->select('company_id')
				->from('users')
				->where('email', $this->email)
				->where('active', true)
                ->whereNull('deleted_at');

			if(config('is_mobile')) {
				$query->where('group_id', '<>', self::GROUP_SUB_CONTRACTOR);
			}
		})->activated(Subscription::ACTIVE)
		->get();

		return $companies;
    }

    // without open api user
    public function scopeNotOpenAPISUser($query)
    {
        return $query->where('group_id', '!=', static::GROUP_OPEN_API);
    }

    /**
	 * get active company of a user who has an account in multiple companies
	 * @param  String | $email | Email of a user
	 * @return $company
	 */
	protected function getMultiUserActiveCompany($email)
	{
		$company = Company::whereIn('id', function($query) use($email){
			$query->select('company_id')
				->from('users')
				->where('email', $email)
				->whereNull('deleted_at')
				->where('active', true);

			if(config('is_mobile')) {
				$query->whereNotIn('group_id', [self::GROUP_SUB_CONTRACTOR, static::GROUP_LABOR]);
			}
		})
		->activated(Subscription::ACTIVE)
		->first();

		return $company;
	}

	public function scopeNotHiddenUser($query)
	{
		$query->where('group_id', '!=', static::GROUP_ANONYMOUS);

		return $query->where('group_id', '!=', static::GROUP_OPEN_API);
	}

	public function virtualNumber()
	{
		return $this->hasOne(TwilioNumber::class);
	}

}
