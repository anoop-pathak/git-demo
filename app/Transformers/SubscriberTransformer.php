<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SubscriberTransformer extends TransformerAbstract
{

    public function transform($company)
    {
        return [
            'id' => (int)$company->id,
            'company_name' => $company->name,
            'office_email' => $company->office_email,
            'office_phone' => $company->office_phone,
            'office_address' => $company->office_address,
            'office_address_line_1' => $company->office_address_line_1,
            'office_fax' => $company->office_fax,
            'office_address' => $company->office_address,
            'office_street' => $company->office_street,
            'office_city' => $company->office_city,
            'office_zip' => $company->office_zip,
            'office_state_id' => (int)$company->office_state,
            'office_state' => $company->state,
            'office_country_id' => (int)$company->office_country,
            'office_country' => $company->country,
            'logo' => $company->logo,
            'created_at' => $company->created_at,
            'subs' => $company->subscriber,
            'account_manager_id' => (int)$company->account_manager_id,
            'account_manager' => $company->accountManager,
        ];
    }
}
