<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Grid\SortableTrait;
use Request;

class AppointmentResultOption extends BaseModel
{
	use SoftDeletes;
    use SortableTrait;
 	/**
    * The database table used by the model
    *
    *  @var string
    */
   	// protected $table = 'appointment_result';
	protected $fillable = ['company_id','name','created_by','fields', 'active'];
	protected function getRules($id = null)
    {
        $input = Request::all();
        $rules = [];
        
        if($id) {
            $rules['name']  = 'required|unique:appointment_result_options,name,'.$id.',id,company_id,'.getScopeId().',deleted_at,NULL';
        } else {
            $rules['name']  = 'required|unique:appointment_result_options,name,null,id,company_id,'.getScopeId().',deleted_at,NULL';
        }
        
        if(ine($input, 'fields')) {
           foreach ($input['fields'] as $key => $value) {
                $rules['fields.' . $key . '.name']  = 'required';
                $rules['fields.' . $key . '.type']  = 'required';
            } 
        } else {
            if(!$id) {
                $rules['fields.0.name']  = 'required';
                $rules['fields.0.type']  = 'required';
            }
        }
        return $rules;
    }
    
    public function setFieldsAttribute($value)
    {
        $this->attributes['fields'] = json_encode($value); 
    }
    
    public function getfieldsAttribute($value)
    {
        return json_decode($value, true); 
    }
    
    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'result_option_id', 'id');
    }

    protected function getOpenAPIRules($id = null)
    {
        $input = Request::all();
        $rules = [];
        
        if($id) {
            $rules['name']  = 'required|unique:appointment_result_options,name,'.$id.',id,company_id,'.getScopeId().',deleted_at,NULL';
        } else {
            $rules['name']  = 'required|unique:appointment_result_options,name,null,id,company_id,'.getScopeId().',deleted_at,NULL';
        }
        
        $rules['fields'] = 'required|array|max:5';
        if(ine($input, 'fields')) {
           $rules['fields.*.name'] = 'required';
           $rules['fields.*.type'] = 'required|in:text,textarea';
        }
        return $rules;
    }

    protected function getAppointmentLinkedUpdateRules($id = null)
    {
        $input = Request::all();
        $rules = [];

        $rules['name']  = 'required|unique:appointment_result_options,name,'.$id.',id,company_id,'.getScopeId().',deleted_at,NULL';

        return $rules;
    }
}