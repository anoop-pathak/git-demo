<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class AccountManagersTransformer extends TransformerAbstract
{

    public function transform($accountManager)
    {
        return [
            'id' => (int)$accountManager->id,
            'first_name' => $accountManager->first_name,
            'last_name' => $accountManager->last_name,
            'full_name' => $accountManager->full_name,
            'full_name_mobile' => $accountManager->full_name_mobile,
            'state_id' => $accountManager->state_id,
            'state' => isset($accountManager->state->name) ? $accountManager->state->name : null,
            'social_security_number' => $accountManager->social_security_number,
            'uuid' => $accountManager->uuid,
            'for_all_trades' => (bool)$accountManager->for_all_trades,
            'trades' => $accountManager->trades->toArray(),
            'email' => $accountManager->email,
            'additional_emails' => $accountManager->additional_emails,
            'contact' => $accountManager->contact,
            'additional_phones' => $accountManager->additional_phones,
            'notes' => $accountManager->notes,
            'subscribers' => $accountManager->subscribers->count(),
            'profile_pic' => empty($accountManager->profile_pic) ? null : FlySystem::publicUrl(\config('jp.BASE_PATH') . $accountManager->profile_pic),
            'address' => $accountManager->address,
            'city' => $accountManager->city,
            'country_id' => $accountManager->country_id,
            'country' => isset($accountManager->country->name) ? $accountManager->country->name : null,
            'zip' => $accountManager->zip,
            'address_line_1' => $accountManager->address_line_1,
            'managing_state_id' => $accountManager->managing_state_id,
            'managing_state' => isset($accountManager->managingState->name) ? $accountManager->managingState->name : null,
        ];
    }
}
