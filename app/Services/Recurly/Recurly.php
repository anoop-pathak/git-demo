<?php

namespace App\Services\Recurly;

use Carbon\Carbon;
use Recurly_Client;
use Recurly_Coupon;
use Recurly_Account;
use Recurly_Invoice;
use Recurly_Adjustment;
use Recurly_CouponList;
use Recurly_BillingInfo;
use Recurly_InvoiceList;
use Recurly_Subscription;
use Recurly_AdjustmentList;

class Recurly
{

    public function __construct()
    {
        Recurly_Client::$subdomain = config('recurly.subdomain');
        Recurly_Client::$apiKey = config('recurly.apiKey');
    }

    public function createAccount($details, $recurlyToken = null)
    {

        $account = new Recurly_Account();
        $account->account_code = $details['account_code'];
        $account->email = $details['email'];
        $account->first_name = $details['first_name'];
        $account->last_name = $details['last_name'];
        $account->company_name = $details['company_name'];

        if ($recurlyToken) {
            $billing_info = new Recurly_BillingInfo();
            $billing_info->token_id = $recurlyToken;
            $account->billing_info = $billing_info;
        }

        $account->create();
        return $account;
    }

    public function updateAccountDetails($accountCode, $details)
    {
        $account = Recurly_Account::get($accountCode);
        if (ine($details, 'email')) {
            $account->email = $details['email'];
        }

        if (ine($details, 'first_name')) {
            $account->first_name = $details['first_name'];
        }

        if (ine($details, 'last_name')) {
            $account->last_name = $details['last_name'];
        }

        if (ine($details, 'company_name')) {
            $account->company_name = $details['company_name'];
        }

        $account->update();

        return true;
    }

    public function addBillingInfo($accountCode, $recurlyToken)
    {

        $billing_info = new Recurly_BillingInfo();
        $billing_info->account_code = $accountCode;
        $billing_info->token_id = $recurlyToken;
        $billing_info->update();

        return $billing_info;
    }

    public function updateBillingInfo($billingDetails)
    {
        $billing_info = new Recurly_BillingInfo();
        $billing_info->account_code = $billingDetails['account_code'];
        $billing_info->address1 = $billingDetails['address1'];
        $billing_info->address2 = $billingDetails['address2'];
        $billing_info->city = $billingDetails['city'];
        $billing_info->state = $billingDetails['state'];
        $billing_info->country = $billingDetails['country'];
        $billing_info->zip = $billingDetails['zip'];
        $billing_info->update();

        return $billing_info;
    }

    public function subscription($data)
    {

        $subscription = new Recurly_Subscription();
        $subscription->plan_code = htmlentities($data['plan_code']);
        $subscription->currency = $data['currency'];
        $subscription->quantity = $data['users_quantity'];

        if (isset($data['starts_at'])
            && $data['starts_at'] > Carbon::now()->toDateTimeString()) {
            $subscription->starts_at = $data['starts_at'];
        }

        $account = new Recurly_Account();
        $account->account_code = htmlentities($data['account_code']);

        $subscription->account = $account;

        // if(isset($data['add_on_code'])) {
        //     $addon = new Recurly_SubscriptionAddOn();
        //     $addon->add_on_code = htmlentities($data['add_on_code']);
        //     $addon->quantity 	= $data['addon_quantity'];
        //     $subscription->subscription_add_ons = array($addon);
        // }

        $subscription->create();
        return $subscription;
    }

    public function upgradeSubscription($data, $subscription = null, $immediately = false)
    {
        if (!$subscription) {
            $subscription = Recurly_Subscription::get($data['uuid']);
        }

        if (isset($data['plan_code'])) {
            $subscription->plan_code = $data['plan_code'];
        }
        $subscription->quantity = $data['users_quantity'];

        if(isset($data['add_on_code'])) {
            $addOns = [];
            if($data['addon_quantity'] > 0) {
                $addon = new \Recurly_SubscriptionAddOn();
                $addon->add_on_code = $data['add_on_code'];
                $addon->quantity    = $data['addon_quantity'];
                $addOns[]           = $addon;
            }
            $subscription->subscription_add_ons = $addOns;
        }

        if ($immediately) {
            try {
                $subscription->updateImmediately();
            } catch (\Exception $e) {
                $subscription->updateAtRenewal();
            }
        } else {
            $subscription->updateAtRenewal();
        }
        if ($immediately && ine($data, 'next_renewal_plan')) {
            $subscription->plan_code = $data['next_renewal_plan'];
            $subscription->updateAtRenewal();
        }
        return $subscription;
    }

    public function getInvoices($accountCode)
    {
        return $invoices = Recurly_InvoiceList::getForAccount($accountCode);
    }

    public function getPDF($invoiceNumber)
    {
        try {
            $pdf = Recurly_Invoice::getInvoicePdf($invoiceNumber);
        } catch (\Recurly_NotFoundError $e) {
            throw new \Exception("Invoice not found");
        }
        return $pdf;
    }

    /**
     * List discount coupon
     * @return array list
     */
    public function coupons()
    {
        $coupons = Recurly_CouponList::get(['state' => 'redeemable']);
        return $coupons;
    }

    /**
     * Redeem discount coupon
     * @param string | Recurly account code
     * @param string | recurly discount coupon
     * @param string | currency code
     * @return bool
     */
    public function redeemCoupon($accountCode, $couponCode, $currency)
    {
        $coupon = Recurly_Coupon::get($couponCode);
        $redemption = $coupon->redeemCoupon($accountCode, $currency);
        return true;
    }

    /**
     * Add charges to recurly account.
     * @param string $accountCode |  Recurly Account code
     * @param float $amount | Charges Amount
     * @param string $description | Description
     * @param string $currency | Currency
     */
    public function addCharges($accountCode, $amount, $description, $currency, $accountingCode = null)
    {

        $charge = new Recurly_Adjustment();
        $charge->account_code = $accountCode;
        $charge->description = $description;
        $charge->unit_amount_in_cents = $amount * 100;
        $charge->currency = $currency;
        // $charge->quantity = 1;
        if ($accountingCode) {
            $charge->accounting_code = $accountingCode;
        }

        // $charge->tax_exempt = false;
        $charge->create();
        return true;
    }

    /**
     * Get all added charges
     * @param string $accountCode |  Recurly Account code
     * @return [type]              [description]
     */
    public function getAddedChargesList($accountCode)
    {
        $adjustments = Recurly_AdjustmentList::get($accountCode);
        return $adjustments;
    }

    /**
     * Get Coupon
     * @param  string $couponCode | Coupon Code
     */
    public function getCoupon($couponCode)
    {
        $coupon = Recurly_Coupon::get($couponCode);
        return $coupon;
    }

    /**
     * Cancel a subscription
     * @param  String $uuid | Recurly Subscription UUID
     * @return bool
     */
    public function cancelSubscription($uuid)
    {
        $subscription = Recurly_Subscription::get($uuid);
        $subscription->cancel();
        return true;
    }

    /**
     * Re-activate cancel subscription
     * @param  String $uuid | Recurly Subscription UUID
     * @return bool
     */
    public function reactivateSubscription($uuid)
    {
        $subscription = Recurly_Subscription::get($uuid);
        $subscription->reactivate();
        return true;
    }

    /**
     * Close recurly account
     * @param  String $accountCode | Recurly Account Code
     * @return true
     */
    public function closeAccount($accountCode)
    {
        $account = Recurly_Account::get($accountCode);
        $account->close();
        return true;
    }

    /**
     * Reopen recurly account
     * @param  String $accountCode | Recurly Account Code
     * @return true
     */
    public function reOpenAccount($accountCode)
    {
        $account = Recurly_Account::get($accountCode);
        $account->reopen();

        return true;
    }

    /**
	 * Get billing info from Recurly Account
	 */
	public function getBillingDetails($accountCode)
	{
		$account = Recurly_BillingInfo::get($accountCode);

		$account = [
			'month' => $account->month,
			'type' => $account->card_type,
			'year' => $account->year,
			'number' => $account->first_six . "********" . $account->last_four
		];

		return $account;
	}
}
