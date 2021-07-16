<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;
use App\Transformers\HoverClientTransformer;
use App\Services\Recurly as RecurlyService;
use App\Transformers\TradesTransformer;
use App\Transformers\CompanyLicenseTransformer;

class CompaniesTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'trades',
        'notes',
        'account_manager',
        'subs',
        'billing',
        'meta',
        'subscription',
        'coupons_redeemed',
        'ev_client',
        'quickbook',
        'google_client',
        'company_cam',
        'sm_client',
        'hover_client',
        'third_party_connections',
        'logos',
        'stage_attribute',
        'license_numbers'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($company)
    {
        return [
            'id' => (int)$company->id,
            'company_name' => $company->name,
            'office_email' => $company->office_email,
            'office_additional_email' => $company->additional_email,
            'office_phone' => $company->office_phone,
            'office_additional_phone' => $company->additional_phone,
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
            'logo' => empty($company->logo) ? null : FlySystem::publicUrl(\config('jp.BASE_PATH') . $company->logo),
            'created_at' => $company->created_at,
            'account_manager_id' => (int)$company->account_manager_id,
            'subscriber_resource_id' => ($company->subscriberResource) ? $company->subscriberResource->value : null,
            'deleted_at' => $company->deleted_at,
            'phone_format' => config("jp.country_phone_masks.{$company->country->code}"),
            'license_number' => $company->license_number,
        ];
    }

    /**
     * Include Notes
     *
     * @return League\Fractal\ItemResource
     */
    public function includeNotes($company)
    {
        $notes = $company->notes;
        if ($notes) {
            return $this->collection($notes, function ($note) {
                return $note->toArray();
            });
        }
    }

    /**
     * Include account_manager
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAccountManager($company)
    {
        $accountManager = $company->accountManager;
        if ($accountManager) {
            return $this->item($accountManager, new AccountManagersTransformer);
        }
    }

    /**
     * Include subs
     *
     * @return League\Fractal\ItemResource
     */
    public function includeBilling($company)
    {
        $billing = $company->billing;
        $card_details = new RecurlyService;
        if ($billing) {
            return $this->item($billing, function ($billing) use ($company, $card_details){
                return [
                    'card_details' => $card_details->getBillingDetails($company->recurly_account_code),
                    'address' => $billing->address,
                    'address_line_1' => $billing->address_line_1,
                    'state_id' => $billing->state_id,
                    'city' => $billing->city,
                    'state' => $billing->state,
                    'country_id' => $billing->country_id,
                    'country' => $billing->country,
                    'zip' => $billing->zip,
                ];
            });
        }
    }

    /**
     * Include subscriber details
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSubs($company)
    {
        $subs = null;
        if ($company->deleted_at) {
            $subs = $company->subscriber()->onlyTrashed()->first();
        } else {
            $subs = $company->subscriber;
        }

        if ($subs) {
            return $this->item($subs, new UsersTransformer);
        }
    }

    /**
     * Include meta
     *
     * @return League\Fractal\ItemResource
     */
    public function includeMeta($company)
    {
        return $this->item($company, function ($company) {
            $users = $company->users()->loggable()->active();
            $subs = $company->subcontractors();

            if ($company->deleted_at) {
                $users->onlyTrashed();
                $subs->onlyTrashed();
            }

            $usersCount = $users->count();
            $subsCount = $subs->count();

            return [
                'users' => $usersCount,
                'subcontractors' => $subsCount,
            ];
        });
    }

    /**
     * Include subscription
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSubscription($company)
    {
        $subscription = $company->subscription;
        if ($subscription) {
            return $this->item($subscription, new SubscriptionTransformer);
        }
    }

    /**
     * Include Coupons Redeemed
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCouponsRedeemed($subscription)
    {
        $coupons = $subscription->redeemedCoupons()->where('is_active', true)->orderBy('id', 'desc')->get();
        return $this->collection($coupons, function ($coupon) {
            $data[$coupon->valid_for] = [
                'id' => $coupon->id,
                'coupon_code' => $coupon->coupon_code,
                'coupon_detail' => $coupon->coupon_detail,
                'valid_for' => $coupon->valid_for,
                'is_active' => $coupon->is_active,
            ];
            return $data;
        });
    }

    /**
     * Include subscription
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEvClient($company)
    {
        $evClient = $company->evClient;
        if ($evClient) {
            return $this->item($evClient, function ($evClient) {
                return [
                    'username' => $evClient->username,
                ];
            });
        }
    }

    public function includeQuickbook($company)
    {
        $quickbook = $company->quickbook;
        if ($quickbook) {
            return $this->item($quickbook, function ($quickbook) {
                return [
                    'quickbook_company_id' => $quickbook->quickbook_id,
                ];
            });
        }
    }

    /**
     * Include Google Account of User
     *
     * @return League\Fractal\ItemResource
     */
    public function includeGoogleClient($user)
    {
        $googleClient = $user->googleClient;

        if ($googleClient) {
            return $this->item($googleClient, function ($googleClient) {
                return [
                    'id' => $googleClient->id,
                    'email' => $googleClient->email
                ];
            });
        }
    }

    /**
     * Include Company Cam
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCompanyCam($user)
    {
        $companyCamClient = $user->companyCamClient;

        if ($companyCamClient) {
            return $this->item($companyCamClient, function ($companyCamClient) {
                return [
                    'id' => $companyCamClient->id,
                    'username' => $companyCamClient->username
                ];
            });
        }
    }

    /**
     * Include subscription
     *
     * @return League\Fractal\ItemResource
     */
    public function includeSmClient($company)
    {
        $smClient = $company->smClient;
        if ($smClient) {
            return $this->item($smClient, function ($smClient) {
                return [
                    'username' => $smClient->username,
                ];
            });
        }
    }

    /**
     * Include hover
     *
     * @return League\Fractal\ItemResource
     */
    public function includeHoverClient($company)
    {
        $hoverClient = $company->hoverClient;
        if($hoverClient) {
            return $this->item($hoverClient, new HoverClientTransformer);
        }
    }

    /**
     * Include third party connections
     *
     * @return League\Fractal\ItemResource
     */

    public function includeThirdPartyConnections($company)
    {
        return $this->item($company, function($company) {
            return [
                'eagleview' => (bool)$company->eagleview,
                'google_sheet' => (bool)$company->google_sheet,
                'quickbook' => (bool)$company->quickbook,
                'hover' => (bool)$company->hover,
                'skymeasure' => (bool)$company->skymeasure,
                'company_cam' => (bool)$company->company_cam,
                'facebook' => (bool)$company->facebook,
                'twitter'  => (bool)$company->twitter,
                'linkedin' => (bool)$company->linkedin,
                'quickbook_desktop' => (bool)$company->quickbook_desktop,
                'abc_supplier'  => (bool)$company->abc_supplier,
                'srs_supplier'   => (bool)$company->srs_supplier,
                'quickbook_pay'  => (bool)$company->quickbookpay
            ];
        });
    }

    /**
     * Include company logos
     *
     * @return League\Fractal\ItemResource
     */
    public function includeLogos($company)
    {
        $companyLogos = $company->companyLogos;
        if($companyLogos) {
            return $this->item($companyLogos, new CompanyLogoTransformer);
        }
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($company)
    {
        $companyTrades = $company->trades;
        /*if($companyTrades) {
            return $this->collection($companyTrades, new TradesTransformer);
        }*/
        if($companyTrades) {
            return $this->collection($companyTrades, function($companyTrades) {
               return [
                    'id'    => $companyTrades->id,
                    'name' => $companyTrades->name
               ];
            });
        }
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeStageAttribute($company)
    {
        $stageAttribute = $company->subscriberLatestStageAttribute;

        if($stage = $stageAttribute->first()) {

             return $this->item($stage, function($stage) {
               return [
                    'id'    => $stage->id,
                    'name'  => $stage->name,
                    'color_code' => $stage->color_code,
                    'created_at' => $stage->created_at
               ];
            });
        }
    }

    public function includeLicenseNumbers($company)
    {
        $licenseNumbers = $company->licenseNumbers;

        return $this->collection($licenseNumbers, new CompanyLicenseTransformer);
    }
}
