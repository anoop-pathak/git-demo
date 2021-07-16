<?php
namespace App\Services\AmericanFoundation\Entities;

use App\Services\AmericanFoundation\Entities\AppEntity;

class AfAppointmentEntity extends AppEntity
{
    protected $af_id;
    protected $af_owner_id;
    protected $name;
    protected $email;
    protected $prospect_id;
    protected $latitude;
    protected $longitude;
    protected $address;
    protected $city;
    protected $county;
    protected $state;
    protected $zip;
    protected $start_time;
    protected $start_datetime;
    protected $end_datetime;
    protected $status;
    protected $type;
    protected $year_home_buillt;
    protected $comments;
    protected $components;
    protected $interests_summary;
    protected $calendar_custom_text;
    protected $appointment_duration;
    protected $price_1;
    protected $price_2;
    protected $price_3;
    protected $quoted_amount;
    protected $result_1;
    protected $result_detail_1;
    protected $result;
    protected $revision_number;
    protected $sales_rep_1_next_appointment;
    protected $sales_rep_2_next_appointment;
    protected $sales_rep_1;
    protected $sales_rep_2;
    protected $source_type;
    protected $source_id;
    protected $talked_to;
    protected $confirmed_by;
    protected $confirmed_at;
    protected $appointment_set_by;
    protected $appointment_set_at;
    protected $product_category_1_type;
    protected $product_category_1;
    protected $issued_by;
    protected $issued_at;
    protected $canceled_by;
    protected $canceled_on;

    protected $created_by;
    protected $updated_by;
    protected $options;

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id                            = ine($data, 'id') ? $data['id'] : null;
        $this->af_owner_id                      = ine($data, 'ownerid') ? $data['ownerid'] : null;
        $this->name                             = ine($data, 'name') ? $data['name'] : null;
        $this->email                            = ine($data, 'i360_email_address_c') ? $data['i360_email_address_c'] : null;
        $this->lead_source_id                   = ine($data, 'i360_lead_source_id_c') ? $data['i360_lead_source_id_c'] : null;
        $this->prospect_id                      = ine($data, 'i360_prospect_id_c') ? $data['i360_prospect_id_c'] : null;
        $this->latitude                         = ine($data, 'i360_latitude_c') ? $data['i360_latitude_c'] : null;
        $this->longitude                        = ine($data, 'i360_longitude_c') ? $data['i360_longitude_c'] : null;
        $this->address                          = ine($data, 'i360_address_c') ? $data['i360_address_c'] : null;
        $this->city                             = ine($data, 'i360_city_c') ? $data['i360_city_c'] : null;
        $this->county                           = ine($data, 'i360_county_c') ? $data['i360_county_c'] : null;
        $this->state                            = ine($data, 'i360_state_c') ? $data['i360_state_c'] : null;
        $this->zip                              = ine($data, 'i360_zip_c') ? $data['i360_zip_c'] : null;
        $this->start_time                       = ine($data, 'i360_start_time_c') ? $data['i360_start_time_c'] : null;
        $this->start_datetime                   = ine($data, 'i360_start_c') ? $data['i360_start_c'] : null;
        $this->end_datetime                     = ine($data, 'i360_end_c') ? $data['i360_end_c'] : null;
        $this->status                           = ine($data, 'i360_status_c') ? $data['i360_status_c'] : null;
        $this->type                             = ine($data, 'i360_type_c') ? $data['i360_type_c'] : null;
        $this->year_home_buillt                 = ine($data, 'i360_year_home_built_c') ? $data['i360_year_home_built_c'] : null;
        $this->comments                         = ine($data, 'i360_comments_c') ? $data['i360_comments_c'] : null;
        $this->components                       = ine($data, 'i360_components_3_c') ? $data['i360_components_3_c'] : null;
        $this->interests_summary                = ine($data, 'i360_interests_summary_c') ? $data['i360_interests_summary_c'] : null;
        $this->calendar_custom_text             = ine($data, 'i360_calendar_custom_text_c') ? $data['i360_calendar_custom_text_c'] : null;
        $this->appointment_duration             = ine($data, 'i360_duration_c') ? $data['i360_duration_c'] : null;
        $this->price_1                          = ine($data, 'i360_price_given_1_c') ? $data['i360_price_given_1_c'] : null;
        $this->price_2                          = ine($data, 'i360_price_given_2_c') ? $data['i360_price_given_2_c'] : null;
        $this->price_3                          = ine($data, 'i360_price_given_3_c') ? $data['i360_price_given_3_c'] : null;
        $this->quoted_amount                    = ine($data, 'i360_quoted_amount_c') ? $data['i360_quoted_amount_c'] : null;
        $this->result_1                         = ine($data, 'i360_result_1_c') ? $data['i360_result_1_c'] : null;
        $this->result_detail_1                  = ine($data, 'i360_result_detail_1_c') ? $data['i360_result_detail_1_c'] : null;
        $this->result                           = ine($data, 'i360_result_c') ? $data['i360_result_c'] : null;
        $this->revision_number                  = ine($data, 'i360_revision_number_c') ? $data['i360_revision_number_c'] : null;
        $this->sales_rep_1_next_appointment     = ine($data, 'i360_sales_rep_1_next_appointment_c') ? $data['i360_sales_rep_1_next_appointment_c'] : null;
        $this->sales_rep_2_next_appointment     = ine($data, 'i360_sales_rep_2_next_appointment_c') ? $data['i360_sales_rep_2_next_appointment_c'] : null;
        $this->sales_rep_1                      = ine($data, 'i360_sales_rep_1_c') ? $data['i360_sales_rep_1_c'] : null;
        $this->sales_rep_2                      = ine($data, 'i360_sales_rep_2_c') ? $data['i360_sales_rep_2_c'] : null;
        $this->source_type                      = ine($data, 'i360_source_type_c') ? $data['i360_source_type_c'] : null;
        $this->source_id                        = ine($data, 'i360_source_c') ? $data['i360_source_c'] : null;
        $this->talked_to                        = ine($data, 'i360_talked_to_c') ? $data['i360_talked_to_c'] : null;
        $this->confirmed_by                     = ine($data, 'i360_confirmed_by_c') ? $data['i360_confirmed_by_c'] : null;
        $this->confirmed_at                     = ine($data, 'i360_confirmed_on_c') ? $data['i360_confirmed_on_c'] : null;
        $this->appointment_set_by               = ine($data, 'i360_appt_set_by_c') ? $data['i360_appt_set_by_c'] : null;
        $this->appointment_set_at               = ine($data, 'i360_appt_set_on_c') ? $data['i360_appt_set_on_c'] : null;
        $this->product_category_1_type          = ine($data, 'supportworks_product_category_1_type_c') ? $data['supportworks_product_category_1_type_c'] : null;
        $this->product_category_1               = ine($data, 'supportworks_product_category_1_c') ? $data['supportworks_product_category_1_c'] : null;
        $this->issued_by                        = ine($data, 'i360_issued_by_c') ? $data['i360_issued_by_c'] : null;
        $this->issued_at                        = ine($data, 'i360_issued_on_c') ? $data['i360_issued_on_c'] : null;
        $this->canceled_by                      = ine($data, 'supportworks_canceled_by_c') ? $data['supportworks_canceled_by_c'] : null;
        $this->canceled_on                      = ine($data, 'supportworks_canceled_on_c') ? $data['supportworks_canceled_on_c'] : null;
        $this->options                          = json_encode($data);
        $this->created_by                       = ine($data, 'createdbyid') ? $data['createdbyid'] : null;
        $this->updated_by                       = ine($data, 'lastmodifiedbyid') ? $data['lastmodifiedbyid'] : null;

    }

    public function get()
    {
        return [
            'company_id'                        => $this->companyId,
            'group_id'                          => $this->groupId,
            'af_id'                             => $this->af_id,
            'af_owner_id'                       => $this->af_owner_id,
            'name'                              => $this->name,
            'email'                             => $this->email,
            'lead_source_id'                    => $this->lead_source_id,
            'prospect_id'                       => $this->prospect_id,
            'latitude'                          => $this->latitude,
            'longitude'                         => $this->longitude,
            'address'                           => $this->address,
            'city'                              => $this->city,
            'county'                            => $this->county,
            'state'                             => $this->state,
            'zip'                               => $this->zip,
            'start_time'                        => $this->start_time,
            'start_datetime'                    => $this->start_datetime,
            'end_datetime'                      => $this->end_datetime,
            'status'                            => $this->status,
            'type'                              => $this->type,
            'year_home_buillt'                  => $this->year_home_buillt,
            'comments'                          => $this->comments,
            'components'                        => $this->components,
            'interests_summary'                 => $this->interests_summary,
            'calendar_custom_text'              => $this->calendar_custom_text,
            'appointment_duration'              => $this->appointment_duration,
            'price_1'                           => $this->price_1,
            'price_2'                           => $this->price_2,
            'price_3'                           => $this->price_3,
            'quoted_amount'                     => $this->quoted_amount,
            'result_1'                          => $this->result_1,
            'result_detail_1'                   => $this->result_detail_1,
            'result'                            => $this->result,
            'revision_number'                   => $this->revision_number,
            'sales_rep_1_next_appointment'      => $this->sales_rep_1_next_appointment,
            'sales_rep_2_next_appointment'      => $this->sales_rep_2_next_appointment,
            'sales_rep_1'                       => $this->sales_rep_1,
            'sales_rep_2'                       => $this->sales_rep_2,
            'source_type'                       => $this->source_type,
            'source_id'                         => $this->source_id,
            'talked_to'                         => $this->talked_to,
            'confirmed_by'                      => $this->confirmed_by,
            'confirmed_at'                      => $this->confirmed_at,
            'appointment_set_by'                => $this->appointment_set_by,
            'appointment_set_at'                => $this->appointment_set_at,
            'product_category_1_type'           => $this->product_category_1_type,
            'product_category_1'                => $this->product_category_1,
            'issued_by'                         => $this->issued_by,
            'issued_at'                         => $this->issued_at,
            'canceled_by'                       => $this->canceled_by,
            'canceled_on'                       => $this->canceled_on,
            'options'                           => $this->options,
            'created_by'                        => $this->created_by,
            'updated_by'                        => $this->updated_by,
            'csv_filename'                      => $this->csv_filename,
        ];
    }
}
