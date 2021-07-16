<?php

namespace App\Commands;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class EVOrderCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $input;
    public $orderData = [];
    public $customerId;
    public $jobId;
    public $productType;
    public $productDeliveryOption;
    public $address = [];
    public $orderFields = [];

    public function __construct($input)
    {
        $this->input = $input;
        $this->extractInput();
    }

    public function extractInput()
    {
        $this->mapAddressFields();
        $this->mapOrderFields();
        $this->mapOrderData();

        $this->customerId            = $this->input['customer_id'];
        $this->jobId                 = $this->input['job_id'];
        $this->productType           = $this->input['ProductTypeName'];
        $this->productDeliveryOption = $this->input['ProductDeliveryOptionName'];
    }

    public function mapAddressFields()
    {
        $addressFields = [
            'Address', 'City', 'State', 'Zip'
        ];
        $this->address = $this->mapInputs($addressFields, $this->input);
        // $this->address['Country'] = 1;
        // if(empty($this->address['Latitude'])) $this->address['Latitude'] = 0;
        // if(empty($this->address['Longitude'])) $this->address['Longitude'] = 0;
        $this->address['AddressType'] = 1;
    }
    

   public function mapOrderFields()
    {
        $orderFields = [
            'PrimaryProductId', 'DeliveryProductId', 'AddOnProductIds', 'MeasurementInstructionType', 'ClaimNumber', 'ClaimInfo', 'BatchId', 'CatId', 'PONumber', 'Comments', 'ReferenceId', 'InsuredName', 'PolicyNumber', 'DateOfLoss'
        ];

        $this->orderFields = $this->mapInputs($orderFields, $this->input);
        $this->orderFields['ChangesInLast4Years'] = ine($this->input, 'ChangesInLast4Years');
    }

    public function mapOrderData()
    {
        $additionalEmails = ine($this->input, 'AdditionalEmails') ? $this->input['AdditionalEmails'] : [];
        // set additional emails
        $this->orderFields['ReportAttibutes'][] = [
            'Attribute' => 24,
            'Value' => implode(':', $additionalEmails),
        ];
        $orderData = $this->orderFields;
        $orderData['ReportAddresses'][] = $this->address;
        $this->orderData['OrderReports'][] = $orderData;
        $this->orderData['PromoCode'] = ine($this->input, 'PromoCode') ? $this->input['PromoCode'] : '';
    }

    public function handle()
    {
        $commandHandler = \App::make(\App\Handlers\Commands\EVOrderCommandHandler::class);
        
        return $commandHandler->handle($this);
    }

    private function mapInputs($map, $input = [])
    {
        $ret = [];
        if (empty($input)) {
            $input = $this->input;
        }
        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($input[$value]) ? $input[$value] : "";
            } else {
                $ret[$key] = isset($input[$value]) ? $input[$value] : "";
            }
        }
        return $ret;
    }
}
