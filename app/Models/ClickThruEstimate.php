<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Grid\SortableTrait;
use FlySystem;

class ClickThruEstimate extends BaseModel
{
	use SortableTrait;
    use SoftDeletes;

	protected $fillable = ['company_id', 'job_id', 'customer_id', 'manufacturer_id', 'level', 'shingle', 'underlayment', 'warranty', 'type', 'roof_size', 'structure', 'complexity', 'pitch', 'chimney', 'others', 'skylight', 'waterproofing', 'gutter', 'access_to_home', 'notes', 'amount', 'adjustable_amount', 'adjustable_note', 'name'
    ];

	protected $dates = ['deleted_at'];

    protected $rules = [
		'job_id' => 'required|integer',
		'name' => 'required',
		'manufacturer_id' => 'required|integer',
		'level_id' => 'required|integer',
		'type_id' => 'required|integer',
		'layer_id' => 'required_if:type_id,2',
		'waterproofing_id' => 'required|integer',
		'shingle_id' => 'required|integer',
		'underlayment_id' => 'required|integer',
		'warranty_id' => 'required|integer',
		'roof_size' => 'required',
		'pitch_id' => 'required|integer',
		'access_to_home' => 'required|in:open,restricted',
	];

    protected $jsonArray =  ['level', 'type', 'shingle', 'underlayment', 'warranty', 'structure', 'complexity', 'pitch', 'chimney', 'others', 'waterproofing', 'gutter', 'access_to_home', 'file'];

    protected $worksheetRulues = [
		'clickthru_id' => 'required|integer',
		'name' => 'required'
	];

    protected function getRules()
	{
		return $this->rules;
	}

    protected function getWorksheetRules()
	{
		return $this->worksheetRulues;
	}

    public function setAttribute($key, $value)
	{
	    // Convert array values to json values
	    if(in_array($key, $this->jsonArray)) {
	        $value = json_encode($value);
	    }
	    parent::setAttribute($key,$value);
	}

    public function getAttribute($key)
	{
		// If the key already exists in the relationships array, it just means the relationship has already been loaded, so we'll just return it out of here because there is no need to query within the relations twice.
	    if (array_key_exists($key, $this->relations))
	    {
	        return $this->relations[$key];
	    }

        // If the "attribute" exists as a method on the model, we will just assume it is a relationship and will load and return results from the query and hydrate the relationship's value on the "relationships" array.
	    $camelKey = camel_case($key);
	    if (method_exists($this, $camelKey))
	    {
	        return $this->getRelationshipFromMethod($key, $camelKey);
	    }
        $value = $this->getAttributeValue($key);

        // Convert json values to array values
	    if(in_array($key, $this->jsonArray)) {
	        $value = json_decode($value, true);
	    }

        return $value;
	}

    public function getUrlAttribute()
	{
		$file = $this->file;
        $url = isset($file['path']) ?  FlySystem::publicUrl(config('jp.BASE_PATH').$file['path']) : null;

		return $url;
	}

    public function getThumbUrlAttribute()
	{
		$file = $this->file;
		$url = isset($file['thumb']) ?  FlySystem::publicUrl(config('jp.BASE_PATH').$file['thumb']) : null;

        return $url;
	}

    public function company()
	{
		return $this->belongsTo(Company::class);
	}

    public function users()
	{
		return $this->belongsToMany(User::class, 'click_thru_estimate_users', 'estimate_id', 'user_id')->withTimestamps();
    }

	public function job()
	{
		return $this->belongsTo(Job::class, 'job_id');
	}

    public function customer()
	{
		return $this->belongsTo(Customer::class, 'customer_id');
	}

    public function jobEstimate()
	{
		return $this->hasOne(Estimation::class, 'clickthru_estimate_id')->whereNull('worksheet_id');
	}
}