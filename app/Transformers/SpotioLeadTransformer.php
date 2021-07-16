<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SpotioLeadTransformer extends TransformerAbstract
{

    public function transform($lead)
    {
        return [
            'id'                                    => $lead->id,
            'company_id'                            => $lead->company_id,
            'lead_id'                               => $lead->lead_id,
            'assigned_user_name'                    => $lead->assigned_user_name,
            'updated_at_external_system_user_id'    => $lead->updated_at_external_system_user_id,
            'assigned_external_system_user_id'      => $lead->assigned_external_system_user_id,
            'address_unit'                          => $lead->address_unit,
            'value'                                 => $lead->value,
            'created_at_utc'                        => $lead->created_at_utc,
            'created_at_local'                      => $lead->created_at_local,
            'updated_at_utc'                        => $lead->updated_at_utc,
            'updated_at_local'                      => $lead->updated_at_local,
            'lat'                                   => $lead->lat,
            'long'                                  => $lead->long,
            'address'                               => $lead->address,
            'city'                                  => $lead->city,
            'house_number'                          => $lead->house_number,
            'street'                                => $lead->street,
            'zip_code'                              => $lead->zip_code,
            'state'                                 => $lead->state,
            'country'                               => $lead->country,
            'stage_name'                            => $lead->stage_name,
            'assigned_user_email'                   => $lead->assigned_user_email,
            'assigned_user_phone'                   => $lead->assigned_user_phone,
            'updated_at_username'                   => $lead->updated_at_username,
            'updated_at_user_email'                 => $lead->updated_at_user_email,
            'company'                               => $lead->company,
            'documents'                             => $lead->documents,
            'documents_list'                        => json_decode($lead->documents_list),
            'last_visit_result'                     => $lead->last_visit_result,
            'contacts'                              => json_decode($lead->contacts),
            'contact_custom_fields'                 => json_decode($lead->contact_custom_fields),
            'lead_custom_fields'                    => json_decode($lead->lead_custom_fields),
            'log_messages'                          => json_decode($lead->log_messages)
        ];
    }
}
