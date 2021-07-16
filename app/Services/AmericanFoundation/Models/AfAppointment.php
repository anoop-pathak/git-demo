<?php

namespace App\Services\AmericanFoundation\Models;

use App\Services\AmericanFoundation\Models\AfCustomer;
use App\Services\AmericanFoundation\Models\AfUser;
use App\Models\BaseModel;
use App\Models\Company;

class AfAppointment extends BaseModel
{

    protected $table = "af_appointments";

    protected $fillable = [
        'company_id', 'group_id', 'appointment_id', 'af_id', 'af_owner_id', 'lead_source_id',
        'prospect_id', 'name', 'email', 'latitude', 'longitude', 'address', 'city', 'county',
        'state', 'zip', 'start_time', 'start_datetime', 'end_datetime', 'status', 'type',
        'year_home_buillt', 'comments', 'components', 'interests_summary', 'calendar_custom_text',
        'appointment_duration', 'price_1', 'price_2', 'price_3', 'quoted_amount', 'result_1',
        'result_detail_1', 'result', 'revision_number', 'sales_rep_1_next_appointment',
        'sales_rep_1', 'sales_rep_2_next_appointment', 'sales_rep_2', 'source_type', 'source_id',
        'talked_to', 'confirmed_by', 'confirmed_at', 'appointment_set_by', 'appointment_set_at',
        'product_category_1_type', 'product_category_1', 'issued_by', 'issued_at', 'canceled_by',
        'canceled_on', 'options', 'created_by', 'updated_by', 'csv_filename'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id', 'id');
    }

    public function afCustomer()
    {
        return $this->belongsTo(AfCustomer::class, 'prospect_id', 'af_id');
    }

    public function afUser()
    {
        return $this->belongsTo(AfUser::class, 'af_owner_id', 'af_id');
    }
}