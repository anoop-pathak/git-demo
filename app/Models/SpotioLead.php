<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\Grid\SortableTrait;

class SpotioLead extends Model
{
	use SortableTrait;

	protected $fillable = ['company_id', 'lead_id', 'assigned_user_name', 'updated_at_external_system_user_id', 'assigned_external_system_user_id', 'address_unit', 'value', 'created_at_utc', 'created_at_local', 'updated_at_utc', 'updated_at_local', 'lat', 'long', 'address', 'city', 'house_number', 'street', 'zip_code', 'state', 'country', 'stage_name', 'assigned_user_email', 'assigned_user_phone', 'updated_at_username', 'updated_at_user_email', 'company', 'documents', 'documents_list', 'last_visit_result', 'contacts', 'contact_custom_fields', 'lead_custom_fields'
    ];
}
