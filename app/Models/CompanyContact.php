<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class CompanyContact extends Model
{

    use SortableTrait;
    use SoftDeletes;

    protected $fillable = ['first_name', 'last_name', 'company_id', 'company_name', 'email', 'phones', 'address', 'note'];

    protected $appends = ['full_name', 'full_name_mobile'];

    protected $rules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'email' => 'email|nullable',
        'phones' => 'required',
        //'tag_ids' => 'required|array'
    ];

    public function tags() {
        return $this->belongsToMany(Tag::class, 'company_contact_tag', 'contact_id', 'tag_id')->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected function getRules()
    {
        return $this->rules;
    }

    public function getPhonesAttribute($value)
    {
        return json_decode($value);
    }

    public function setPhonesAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['phones'] = json_encode($value);
    }

    public function setFirstNameAttribute($value)
    {
        $this->attributes['first_name'] = ucfirst($value);
    }

    public function setLastNameAttribute($value)
    {
        $this->attributes['last_name'] = ucfirst($value);
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

    // set Tags scope for query filtering.
    public function scopeTags($query, $tagIds){
        $query->whereIn('contacts.id',function($query) use($tagIds){
            $query->select('contact_id')->from('contact_tag')->whereIn('tag_id', (array)$tagIds);
        });
    }

    /**
     * **
     * Compoany Contact soft delete
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($companyContact) {
            $companyContact->deleted_by = \Auth::user()->id;
            $companyContact->save();
        });
    }
}
