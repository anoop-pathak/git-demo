<?php

namespace App\Http\CustomerWebPage\Transformers;
use FlySystem;

use League\Fractal\TransformerAbstract;

class CompaniesTransformer extends TransformerAbstract
{

    public function transform($company)
    {
        return [
            'id' => (int)$company->id,
            'company_name' => $company->name,
            'office_email' => $company->office_email,
            'additional_email' => $company->additional_email,
            'office_phone' => $company->office_phone,
            'additional_phone' => $company->additional_phone,
            'office_address'   => $company->office_address,
            'office_address_line_1' => $company->office_address_line_1,
            'office_fax'    => $company->office_fax,
            'office_street' => $company->office_street,
            'office_city' => $company->office_city,
            'office_zip'  => $company->office_zip,
            'office_state' => $company->state,
            'office_country' => $company->country,
            'logo' => FlySystem::getUrl(config('jp.BASE_PATH').$company->logo),
            'phone_format'   => config("jp.country_phone_masks.{$company->country->code}"),
            'license_number' => $company->license_number,
        ];
    }
}
