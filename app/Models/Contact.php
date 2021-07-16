<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Grid\SortableTrait;
use Nicolaslopezj\Searchable\SearchableTrait;
use Laracasts\Presenter\PresentableTrait;
use Request;

class Contact extends BaseModel
{
	use SortableTrait, SearchableTrait, PresentableTrait;
	use SoftDeletes;

	protected $table = 'contacts';

	protected $fillable = ['company_id', 'type', 'company_name', 'first_name', 'last_name', 'address_id', 'created_by', 'last_modified_by'];

	protected $searchable = [];

	protected $presenter = 'App\Presenter\JobContactPresenter';

	const TYPE_JOB = 'job';
    const TYPE_COMPANY = 'company';

    /***** Validation Rules *****/

	protected function getValidationRules()
	{
		$input = Request::all();

		$rules = [
			'first_name' => 'max:100',
			'last_name' => 'max:100',
			'company_name' => 'max:100',
			'phones' => 'array|max_primary:1',
			'emails' => 'array|max_primary:1',
		];

		if(isset($input['emails'])) {
			foreach ((array)$input['emails'] as $key => $val) {
				$rules['emails.'.$key.'.email'] = 'required|email';
				$rules['emails.'.$key.'.is_primary'] = 'boolean';
			}
		}

		if(isset($input['phones'])) {
			foreach($input['phones'] as $key => $val) {
				$rules['phones.' . $key . '.label']  = 'in:home,cell,phone,office,fax,other';
				$rules['phones.' . $key . '.number']  = 'customer_phone:8,12';
				$rules['phones.' . $key . '.is_primary']  = 'boolean';
			}
		}

		return $rules;
	}

	protected function getJobRules()
	{
		$rules = self::getValidationRules();
		$rules['job_id'] = 'required';
		$rules['type'] = 'in:job,company';

		return $rules;
	}

	protected function getUnlinkContactRules()
	{
		$rules = [
			'job_id' => 'required',
			'contact_id' => 'required',
		];

		return $rules;
	}

	protected function getTagValidationRules()
	{
		$input = Request::all();
		$rules = [
			'tag_ids' => 'array',
		];
		if(isset($input['tag_ids'])) {
			foreach ((array)$input['tag_ids'] as $key => $val) {
				$rules['tag_ids.'.$key] = 'required';
			}
		}
		return $rules;
	}

	protected function assignMultipleTagRules()
	{
		$input = Request::all();
		$rules = [
			'contact_ids' => 'required|array',
			'tag_ids' => 'required|array',
		];

		return $rules;
	}

	/***** Validation Rules End *****/

	public function getFullNameAttribute()
	{
	    if(empty($this->last_name)) {

	        return $this->first_name;
	    }

	    return $this->first_name.' '.$this->last_name;
	}

	public function getFullNameMobileAttribute()
	{

	    if(empty($this->last_name)) {

	        return $this->first_name;
	    }

	    return $this->first_name.' '.$this->last_name;
	}

	public function address()
    {
        return $this->belongsTo(Address::class);
    }

	public function phones(){
		return $this->belongsToMany(Phone::class, 'contact_phone', 'contact_id', 'phone_id')->withPivot('is_primary')->withTimestamps();
	}

	public function emails(){
		return $this->belongsToMany(EmailAddress::class, 'contact_email', 'contact_id', 'email_address_id')
			->withPivot('is_primary')
			->withTimestamps();
	}

	public function contactPrimaryEmail()
	{
		return $this->belongsToMany(EmailAddress::class, 'contact_email', 'contact_id', 'email_address_id')
			->withPivot('is_primary')
			->withTimestamps()
			->where('is_primary', true)
			->take(1);
	}

	public function tags() {
		return $this->belongsToMany(Tag::class, 'contact_tag', 'contact_id', 'tag_id')->withTimestamps();
	}

	public function jobContact() {
		return $this->hasMany(JobContact::class);
	}

	public function notes() {
		return $this->hasMany(ContactNote::class);
	}

	public function jobs()
	{
		return $this->belongsToMany(Job::class, 'job_contact', 'contact_id', 'job_id');
	}

	public function isPrimary()
	{
		if(isset($this->is_primary)) return $this->is_primary;

		if(!$this->pivot) return 0;

		return (int)$this->pivot->is_primary;
	}

	public function isJobContact()
	{
		return ($this->type == Self::TYPE_JOB);
	}

	public function primaryEmail()
	{
		$primaryEmail = $this->contactPrimaryEmail->first();

		return $primaryEmail ? $primaryEmail->email : null;
	}

	/***** Scopes *****/

	public function scopeJobs($query, $jobId)
	{
		$query->where(function($query) use($jobId){
			$query->whereIn('contacts.id' , function($query) use($jobId){
				$query->select('contact_id')->from('job_contact')->whereIn('job_id', (array)$jobId);
			});
		});
	}

	public function scopeCompanyContacts($query)
	{
		$query->where('type', self::TYPE_COMPANY);
	}

	public function scopeKeywordSearch($query, $keyword)
	{
		$this->searchable = [
			'columns' => [
				'contacts.first_name'	=> 10,
				'contacts.last_name'	=> 10,
				'contacts.company_name'	=> 10,
			],
		];

		$query->search(implode(' ', array_slice(explode(' ', $keyword), 0, 10)), null, true);
	}

	/***** Scopes End *****/

	// public static function boot()
	// {
	// 	parent::boot();

	// 	static::saving(function($model) {
	// 		if(Auth::check()) {
	// 			$model->created_by = Auth::id();
	// 			$model->last_modified_by = Auth::id();
	// 		}
	// 	});

	// 	static::updating(function($model) {
	// 		if(Auth::check()) {
	// 			$model->last_modified_by = Auth::id();
	// 		}
	// 	});
	// }
}