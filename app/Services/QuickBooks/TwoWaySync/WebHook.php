<?php
namespace App\Services\QuickBooks\TwoWaySync;

class WebHook
{

    // Webhook Enities

    const Customer = 'Customer';
    
    const CreditMemo = 'CreditMemo';

    const Department = 'Department';

    const Invoice = 'Invoice';

    const Payment = 'Payment';

    const Item = 'Item';
    
    const Account = "Account";

    const Vendor = "Vendor";

    const Bill = "Bill";

    const PaymentMethod = 'PaymentMethod';

    const RefundReceipt = "RefundReceipt";

    // Webhook operations

    const Create = 'Create';

    const Update = 'Update';

    const Delete = 'Delete';

    public static $events = [
        self::Customer,
        self::CreditMemo,
        self::Department,
        self::Invoice,
        self::Payment,
        self::Item,
        self::PaymentMethod,
        self::Account,
        self::Vendor,
        self::Bill,
        self::RefundReceipt
    ];
}