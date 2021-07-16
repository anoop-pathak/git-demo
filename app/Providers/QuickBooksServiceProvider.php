<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\QuickBooks\TwoWaySync\Service;
use App\Services\QuickBooks\Entity\Customer;
use App\Services\QuickBooks\Entity\Payment;
use App\Services\QuickBooks\Entity\Item;
use App\Services\QuickBooks\Entity\Invoice;
use App\Services\QuickBooks\Entity\CreditMemo;
use App\Services\QuickBooks\Entity\Department;
use App\Services\QuickBooks\Entity\PaymentMethod;
use App\Services\QuickBooks\Entity\Account;
use App\Services\QuickBooks\Entity\Vendor;
use App\Services\QuickBooks\QBOQueueHandler;
use App\Services\QuickBooks\Entity\SyncRequest;
use App\Services\QuickBooks\Entity\Bill;
use App\Services\QuickBooks\Entity\Attachable;
use App\Services\QuickBooks\Entity\Refund;

class QuickBooksServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->singleton('qb-service', function () {
            return new Service;
        });

        $this->app->singleton('qb-customer', function () {
            return new Customer;
        });

        $this->app->singleton('qb-payment', function () {
            return new Payment;
        });

        $this->app->singleton('qb-item', function () {
            return new Item;
        });

        $this->app->singleton('qb-invoice', function () {
            return new Invoice;
        });

        $this->app->singleton('qb-credit-memo', function () {
            return new CreditMemo;
        });

        $this->app->singleton('qb-department', function () {
            return new Department;
        });

        $this->app->singleton('qb-payment-method', function () {
            return new PaymentMethod;
        });

        $this->app->singleton('qb-account', function () {
            return new Account;
        });

        $this->app->singleton('qb-queue', function () {
            return new QBOQueueHandler;
        });

        $this->app->singleton('qb-sync-request', function () {
            return new SyncRequest;
        });

        $this->app->singleton('qb-vendor', function () {
            return new Vendor;
        });

        $this->app->singleton('qb-bill', function () {
            return new Bill;
        });

        $this->app->singleton('qb-attachable', function () {
            return new Attachable;
        });

        $this->app->singleton('qb-refund', function () {
            return new Refund;
        });
    }
}