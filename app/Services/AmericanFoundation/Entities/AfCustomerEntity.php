<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfCustomerEntity extends AppEntity
{
    protected $af_id;
    protected $customer_id;
    protected $rep_id;
    protected $referred_by_type;
    protected $company_name;
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $secondary_first_name;
    protected $secondary_last_name;
    protected $billing_address;
    protected $billing_city;
    protected $billing_state;
    protected $billing_zip;
    protected $customer_address;
    protected $customer_city;
    protected $customer_state;
    protected $customer_zip;
    protected $management_company;
    protected $property_name;
    protected $origin;
    protected $note;
    protected $options = [];

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id                = ine($data, 'id') ? $data['id'] : null;
        $this->rep_id               = ine($data, 'ownerid') ? $data['ownerid'] : null;
        $this->referred_by_type     = ine($data, 'referred_by_type') ? $data['referred_by_type'] : null;
        $this->company_name         = ine($data, 'supportworks_company_c') ? $data['supportworks_company_c'] : null;
        $this->first_name           = ine($data, 'i360_primary_first_name_c') ? $data['i360_primary_first_name_c'] : null;
        $this->last_name            = ine($data, 'i360_primary_last_name_c') ? $data['i360_primary_last_name_c'] : null;
        $this->email                = ine($data, 'i360_primary_email_c') ? $data['i360_primary_email_c'] : null;
        $this->secondary_first_name = ine($data, 'i360_secondary_first_name_c') ? $data['i360_secondary_first_name_c'] : null;
        $this->secondary_last_name  = ine($data, 'i360_secondary_last_name_c') ? $data['i360_secondary_last_name_c'] : null;
        $this->billing_address      = ine($data, 'supportworks_billing_address_c') ? $data['supportworks_billing_address_c'] : null;
        $this->billing_city         = ine($data, 'supportworks_billing_city_c') ? $data['supportworks_billing_city_c'] : null;
        $this->billing_state        = ine($data, 'supportworks_billing_state_c') ? $data['supportworks_billing_state_c'] : null;
        $this->billing_zip          = ine($data, 'supportworks_billing_zip_c') ? $data['supportworks_billing_zip_c'] : null;
        $this->customer_address     = ine($data, 'supportworks_mailing_address_c') ? $data['supportworks_mailing_address_c'] : null;
        $this->customer_city        = ine($data, 'supportworks_mailing_city_c') ? $data['supportworks_mailing_city_c'] : null;
        $this->customer_state       = ine($data, 'supportworks_mailing_state_c') ? $data['supportworks_mailing_state_c'] : null;
        $this->customer_zip         = ine($data, 'supportworks_mailing_zip_postal_code_c') ? $data['supportworks_mailing_zip_postal_code_c'] : null;
        $this->management_company   = ine($data, 'management_company') ? $data['management_company'] : null;
        $this->property_name        = ine($data, 'property_name') ? $data['property_name'] : null;
        $this->origin               = ine($data, 'origin') ? $data['origin'] : 'JobProgress';
        $this->options              = json_encode($data);
        $this->note                 = $this->setNote($data);

    }

    public function get()
    {
        return [
            'af_id'            => $this->af_id,
            'company_id'            => $this->companyId,
            'group_id'              => $this->groupId,
            // 'customer_id'           => $this->customer_id,
            'rep_id'                => $this->rep_id,
            'referred_by_type'      => $this->referred_by_type,
            'company_name'          => $this->company_name,
            'first_name'            => $this->first_name,
            'last_name'             => $this->last_name,
            'email'                 => $this->email,
            'secondary_first_name'  => $this->secondary_first_name,
            'secondary_last_name'   => $this->secondary_last_name,
            'billing_address'       => $this->billing_address,
            'billing_city'          => $this->billing_city,
            'billing_state'         => $this->billing_state,
            'billing_zip'           => $this->billing_zip,
            'customer_address'      => $this->customer_address,
            'customer_city'         => $this->customer_city,
            'customer_state'        => $this->customer_state,
            'customer_zip'          => $this->customer_zip,
            'management_company'    => $this->management_company,
            'property_name'         => $this->property_name,
            'origin'                => $this->origin,
            'note'                  => $this->note,
            'options'               => $this->options,
            'csv_filename'          => $this->csv_filename,
        ];
    }

    private function setNote($data)
    {
        $note = null;

        if(ine($data, 'i361_dnc_waiver_description_1_c')) {
            $note .= "i361_dnc_waiver_description_1_c:- " . $data['i361_dnc_waiver_description_1_c'] . "\n";
        }

        if(ine($data, 'i361_dnc_waiver_description_2_c')) {
            $note .= "i361_dnc_waiver_description_2_c:- " . $data['i361_dnc_waiver_description_2_c'] . "\n";
        }

        if(ine($data, 'i361_dnc_waiver_description_3_c')) {
            $note .= "i361_dnc_waiver_description_3_c:- " . $data['i361_dnc_waiver_description_3_c'] . "\n";
        }

        if(ine($data, 'i360_comments_c')) {
            $note .= "i360_comments_c:- " . $data['i360_comments_c'] . "\n";
        }
        if(ine($data, 'i360_home_value_c')) {
            $note .= "i360_home_value_c:- " . $data['i360_home_value_c'] . "\n";
        }

        if(ine($data, 'i360_not_qualified_reason_c')) {
            $note .= "i360_not_qualified_reason_c:- " . $data['i360_not_qualified_reason_c'] . "\n";
        }

        if(ine($data, 'i360_restriction_comments_c')) {
            $note .= "i360_restriction_comments_c:- " . $data['i360_restriction_comments_c'] . "\n";
        }

        if(ine($data, 'i360_year_home_built_c')) {
            $note .= "i360_year_home_built_c:- " . $data['i360_year_home_built_c'] . "\n";
        }

        if(ine($data, 'i360_year_home_purchased_c')) {
            $note .= "i360_year_home_purchased_c:- " . $data['i360_year_home_purchased_c'] . "\n";
        }

        if(ine($data, 'supportworks_company_c')) {
            $note .= "supportworks_company_c:- " . $data['supportworks_company_c'] . "\n";
        }

        if(ine($data, 'supportworks_next_annual_maintenance_date_c')) {
            $note .= "supportworks_next_annual_maintenance_date_c:- " . $data['supportworks_next_annual_maintenance_date_c'] . "\n";
        }

        if(ine($data, 'supportworks_service_appointment_date_c')) {
            $note .= "supportworks_service_appointment_date_c:- " . $data['supportworks_service_appointment_date_c'] . "\n";
        }

        if(ine($data, 'supportworks_annual_maintenance_content_c')) {
            $note .= "supportworks_annual_maintenance_content_c:- " . $data['supportworks_annual_maintenance_content_c'] . "\n";
        }

        if(ine($data, 'supportworks_most_recent_product_category_c')) {
            $note .= "supportworks_most_recent_product_category_c:- " . $data['supportworks_most_recent_product_category_c'] . "\n";
        }
        return $note;
    }
}
