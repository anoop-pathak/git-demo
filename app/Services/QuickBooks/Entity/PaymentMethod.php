<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\PaymentMethodsRepository;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentMethod
{

    public function __construct(
        PaymentMethodsRepository $repo
    ) {
        $this->paymentMethodsRepo = $repo;
    }

    public function create($id)
    {
        try {

            DB::beginTransaction();

            $response = $this->get($id);

            if (!ine($response, 'entity')) {
                throw new Exception("Unable to fetch payment method details from QuickBooks");
            }

            $paymentMethod = $response['entity'];
            
            $paymentMethod = QuickBooks::toArray($paymentMethod);
            
            $jpPaymentMethod = $this->paymentMethodsRepo->getByLabel($paymentMethod['Name']);

            if ($jpPaymentMethod) {
                DB::commit();
                return $jpPaymentMethod;
                // throw new Exception("Payment method already exists");
            }   

            $method = Str::slug($paymentMethod['Name'], '_');

            $jpPaymentMethod = $this->paymentMethodsRepo->create(
                    $paymentMethod['Name'],
                    $method,
                    $paymentMethod['Id'],
                    $paymentMethod['SyncToken']
                );
            
            DB::commit();

            return $jpPaymentMethod;
            
        } catch (Exception $e) {

            DB::rollback();

            Log::error('Unable to create customer', [(string) $e]);

            throw $e;
        }
    }
    
    public function update($id)
    {
        try {

            DB::beginTransaction();

            $response = $this->get($id);

            if (!ine($response, 'entity')) {
                throw new Exception("Unable to fetch payment method details from QuickBooks");
            }

            $paymentMethod = $response['entity'];

            $paymentMethod = QuickBooks::toArray($paymentMethod);

            $jpPaymentMethod = $this->paymentMethodsRepo->getByQBId($paymentMethod['Id']);

            if($jpPaymentMethod) {

                $method = Str::slug($paymentMethod['Name'], '_');

                $jpPaymentMethod = $this->paymentMethodsRepo->update(
                    $jpPaymentMethod->id,
                    $paymentMethod['Name'],
                    $method,
                    $paymentMethod['Id'],
                    $paymentMethod['SyncToken']
                );

                DB::commit();

                return $jpPaymentMethod;
            }

            $jpPaymentMethod = $this->paymentMethodsRepo->getByLabel($paymentMethod['Name']);

            if ($jpPaymentMethod) {
                DB::commit();
                return $jpPaymentMethod;
                // throw new Exception("Payment method already exists");
            }
            
            DB::commit();

            return $jpPaymentMethod;

        } catch (Exception $e) {

            DB::rollback();
            Log::error('Unable to create customer', [(string) $e]);

            throw $e;
        }
    }

    public function get($id)
    {
        return QuickBooks::findById('payment_method', $id);
    }
}